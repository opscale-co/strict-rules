<?php

declare(strict_types=1);

namespace Opscale\Rules\SOLID\DIP;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that prevents direct instantiation of classes to enforce DIP.
 *
 * Walks every classlike (Class_, Trait_, Enum_) declared in the file.
 *
 * Allowed instantiations are computed in three layers:
 *  1. PHP built-in classes (no namespace).
 *  2. A fixed list of canonical Laravel / Carbon classes.
 *  3. Any class whose reflection is a subclass of one of the canonical
 *     base classes for legitimately-instantiable Laravel patterns:
 *     Eloquent\Model, Mail\Mailable, Notifications\Notification,
 *     Http\Resources\Json\JsonResource.
 *  4. A heuristic suffix list (DTO, ValueObject, Value, Data, Request,
 *     Response, Event).
 *  5. self / parent / static.
 *
 * The rule still skips constructors entirely (initialisation is the
 * one place where `new` is acceptable for fields that the class owns).
 */
class DisallowInstantiationRule extends BaseRule
{
    private const ALLOWED_INSTANTIATIONS = [
        'Illuminate\\Support\\Collection',
        'Illuminate\\Http\\Request',
        'Illuminate\\Http\\Response',
        'Illuminate\\Http\\JsonResponse',
        'Illuminate\\Http\\RedirectResponse',
        'Illuminate\\Validation\\ValidationException',
        'Illuminate\\Database\\Eloquent\\Collection',
        'Illuminate\\Pagination\\LengthAwarePaginator',
        'Illuminate\\Pagination\\Paginator',
        'Carbon\\Carbon',
        'Carbon\\CarbonImmutable',
    ];

    private const ALLOWED_BASE_CLASSES = [
        Model::class,
        Mailable::class,
        Notification::class,
        JsonResource::class,
    ];

    private const VALUE_OBJECT_SUFFIXES = [
        'DTO',
        'ValueObject',
        'Value',
        'Data',
        'Request',
        'Response',
        'Event',
    ];

    /**
     * @var array<string>
     */
    private array $additionalAllowedClasses;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        array $additionalAllowedClasses = []
    ) {
        parent::__construct($reflectionProvider);
        $this->additionalAllowedClasses = $additionalAllowedClasses;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];

        foreach ($this->getClassLikeNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }

            foreach ($this->getMethodNodes($classNode) as $method) {
                if ($method->name->toString() === '__construct') {
                    continue;
                }

                foreach ($this->findNewExpressions($method) as $new) {
                    $error = $this->checkInstantiation($new, $method, $classNode, $node);
                    if ($error !== null) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return $errors;
    }

    private function checkInstantiation(
        New_ $new,
        ClassMethod $method,
        Class_|Trait_|Enum_ $classNode,
        FileNode $fileNode
    ): ?IdentifierRuleError {
        if (! $new->class instanceof Name) {
            return null;
        }

        $rawClassName = $new->class->toString();
        if ($this->isSelfOrParentInstantiation($rawClassName)) {
            return null;
        }

        $resolvedClassName = $this->resolveClassName($rawClassName, $fileNode);

        if ($this->isAllowedInstantiation($resolvedClassName)) {
            return null;
        }

        return RuleErrorBuilder::message(sprintf(
            'Class "%s" violates Dependency Inversion Principle by directly instantiating "%s" in method "%s()". '.
            'Consider injecting the dependency through constructor or method parameters.',
            $classNode->namespacedName?->toString() ?? 'Unknown',
            $resolvedClassName,
            $method->name->toString()
        ))
            ->line($new->getLine())
            ->identifier('solid.dip.disallowInstantiation')
            ->build();
    }

    /**
     * @return list<New_>
     */
    private function findNewExpressions(Node $node): array
    {
        $found = [];
        if ($node instanceof New_) {
            $found[] = $node;
        }

        foreach ($node->getSubNodeNames() as $subName) {
            $sub = $node->$subName;
            if ($sub instanceof Node) {
                $found = array_merge($found, $this->findNewExpressions($sub));
            } elseif (is_array($sub)) {
                foreach ($sub as $item) {
                    if ($item instanceof Node) {
                        $found = array_merge($found, $this->findNewExpressions($item));
                    }
                }
            }
        }

        return $found;
    }

    private function resolveClassName(string $className, FileNode $fileNode): string
    {
        // PHPStan's NameResolver pre-resolves the Name node to its FQCN, so
        // `$new->class->toString()` already returns the fully-qualified name.
        // We only strip a stray leading backslash for fully-qualified writes
        // like `new \Foo\Bar()`.
        return ltrim($className, '\\');
    }

    private function isAllowedInstantiation(string $className): bool
    {
        if ($this->isPhpBuiltInClass($className)) {
            return true;
        }

        foreach (self::ALLOWED_INSTANTIATIONS as $allowed) {
            if ($className === $allowed || str_ends_with($className, '\\'.$allowed)) {
                return true;
            }
        }

        foreach ($this->additionalAllowedClasses as $additional) {
            if ($className === $additional || str_ends_with($className, '\\'.$additional)) {
                return true;
            }
        }

        if ($this->isSubclassOfAllowedBase($className)) {
            return true;
        }

        foreach (self::VALUE_OBJECT_SUFFIXES as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function isSubclassOfAllowedBase(string $className): bool
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $reflection = $this->reflectionProvider->getClass($className);

        foreach (self::ALLOWED_BASE_CLASSES as $base) {
            if ($reflection->getName() === $base) {
                return true;
            }
            if ($reflection->isSubclassOf($base)) {
                return true;
            }
        }

        return false;
    }

    private function isPhpBuiltInClass(string $className): bool
    {
        if (str_contains($className, '\\')) {
            return false;
        }

        try {
            if ($this->reflectionProvider->hasClass($className)) {
                $reflection = $this->reflectionProvider->getClass($className);

                return $reflection->isBuiltin();
            }
        } catch (Exception) {
            return false;
        }

        return false;
    }

    private function isSelfOrParentInstantiation(string $className): bool
    {
        return in_array(strtolower($className), ['self', 'parent', 'static'], true);
    }

    /**
     * @return list<Class_|Trait_|Enum_>
     */
    private function getClassLikeNodes(FileNode $fileNode): array
    {
        $nodes = [];
        foreach ($fileNode->getNodes() as $stmt) {
            if (! $stmt instanceof Namespace_) {
                continue;
            }
            foreach ($stmt->stmts as $inner) {
                if ($inner instanceof Class_ ||
                    $inner instanceof Trait_ ||
                    $inner instanceof Enum_) {
                    $nodes[] = $inner;
                }
            }
        }

        return $nodes;
    }
}
