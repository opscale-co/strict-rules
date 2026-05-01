<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that enforces every concrete Value Object class under
 * `\Models\ValueObjects\*` to honour the
 * `Illuminate\Contracts\Database\Eloquent\CastsAttributes` contract,
 * directly or through inheritance / interface extension.
 *
 * The rule walks every Class_ in the file (multi-class files supported)
 * and uses ClassReflection::getInterfaces(), which already covers
 * transitive interface implementation through parent classes and
 * interface inheritance.
 *
 * Abstract classes are skipped — they are infrastructure for VOs, not
 * VOs themselves; the concrete subclass is what must satisfy the
 * contract.
 */
class EnforceCastRule extends BaseRule
{
    private const VALUE_OBJECTS_NAMESPACE = '\\Models\\ValueObjects';

    private const ERROR_TEMPLATE = 'ValueObject class "%s" must implement "%s" interface '.
        '(directly, via a parent class, or via interface inheritance). The cast contract is '.
        'required for every concrete class under "\\Models\\ValueObjects".';

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (! $node instanceof FileNode) {
            return false;
        }

        $namespace = $this->getNamespace($node);

        return $this->isInNamespaces($namespace, [self::VALUE_OBJECTS_NAMESPACE]);
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];

        foreach ($this->getClassNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }
            if ($classNode->isAbstract()) {
                continue;
            }

            $fqcn = $classNode->namespacedName->toString();
            if (! $this->reflectionProvider->hasClass($fqcn)) {
                continue;
            }

            $reflection = $this->reflectionProvider->getClass($fqcn);
            if ($this->implementsCastsAttributes($reflection)) {
                continue;
            }

            $errors[] = $this->buildError($fqcn, $classNode);
        }

        return $errors;
    }

    private function implementsCastsAttributes(ClassReflection $reflection): bool
    {
        foreach ($reflection->getInterfaces() as $interface) {
            if ($interface->getName() === CastsAttributes::class) {
                return true;
            }
        }

        return false;
    }

    private function buildError(string $fqcn, Class_ $classNode): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(self::ERROR_TEMPLATE, $fqcn, CastsAttributes::class))
            ->line($classNode->getLine())
            ->identifier('ddd.valueObjects.enforceCast')
            ->build();
    }
}
