<?php

namespace Opscale\Rules\SOLID\DIP;

use Exception;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that prevents direct instantiation of classes to enforce Dependency Inversion Principle
 */
class DisallowInstantiationRule extends BaseRule
{
    /**
     * Classes that are allowed to be instantiated directly
     */
    private const ALLOWED_INSTANTIATIONS = [
        // Laravel/Illuminate classes commonly instantiated
        'Illuminate\\Support\\Collection',
        'Illuminate\\Http\\Request',
        'Illuminate\\Http\\Response',
        'Illuminate\\Http\\JsonResponse',
        'Illuminate\\Http\\RedirectResponse',
        'Illuminate\\Validation\\ValidationException',
        'Illuminate\\Database\\Eloquent\\Collection',
        'Illuminate\\Pagination\\LengthAwarePaginator',
        'Illuminate\\Pagination\\Paginator',

        // Common data transfer objects and value objects patterns
        'Carbon\\Carbon',
        'Carbon\\CarbonImmutable',
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

    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            ! $this->shouldProcess($node, $scope)) {
            return [];
        }

        $errors = [];
        $rootNode = $this->getRootNode($node);

        if ($rootNode === null) {
            return [];
        }

        // Check all methods in the class
        foreach ($this->getMethodNodes($rootNode) as $method) {
            $methodErrors = $this->checkMethodForInstantiations($method, $node);
            $errors = array_merge($errors, $methodErrors);
        }

        return $errors;
    }

    /**
     * Check a method for direct class instantiations
     *
     * @return RuleError[]
     */
    private function checkMethodForInstantiations(ClassMethod $method, FileNode $fileNode): array
    {
        $errors = [];
        $classReflection = $this->getClassReflection($fileNode);

        if ($classReflection === null) {
            return [];
        }

        // Skip constructor method as it's expected to have instantiations for initialization
        if ($method->name->toString() === '__construct') {
            return [];
        }

        // Recursively find all 'new' expressions in the method
        $newExpressions = $this->findNewExpressions($method);

        foreach ($newExpressions as $newExpression) {
            if (! $newExpression->class instanceof Node\Name) {
                continue;
            }

            $instantiatedClass = $newExpression->class->toString();

            // Resolve the full class name considering use statements
            $resolvedClassName = $this->resolveClassName($instantiatedClass, $fileNode);

            // Skip if it's an allowed instantiation
            if ($this->isAllowedInstantiation($resolvedClassName)) {
                continue;
            }

            // Skip if it's a self or parent instantiation
            if ($this->isSelfOrParentInstantiation($instantiatedClass)) {
                continue;
            }

            $error = sprintf(
                'Class "%s" violates Dependency Inversion Principle by directly instantiating "%s" in method "%s()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                $classReflection->getName(),
                $resolvedClassName,
                $method->name->toString()
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($newExpression->getLine())
                ->identifier('solid.dip.disallowInstantiation')
                ->build();
        }

        return $errors;
    }

    /**
     * Recursively find all 'new' expressions in a node
     *
     * @return New_[]
     */
    private function findNewExpressions(Node $node): array
    {
        $newExpressions = [];

        if ($node instanceof New_) {
            $newExpressions[] = $node;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;

            if ($subNode instanceof Node) {
                $newExpressions = array_merge($newExpressions, $this->findNewExpressions($subNode));
            } elseif (is_array($subNode)) {
                foreach ($subNode as $arrayItem) {
                    if ($arrayItem instanceof Node) {
                        $newExpressions = array_merge($newExpressions, $this->findNewExpressions($arrayItem));
                    }
                }
            }
        }

        return $newExpressions;
    }

    /**
     * Resolve a class name considering use statements
     */
    private function resolveClassName(string $className, FileNode $fileNode): string
    {
        // If it's already a fully qualified name, return as is
        if (str_starts_with($className, '\\')) {
            return ltrim($className, '\\');
        }

        // Check if it's imported via use statement
        $useStatements = $this->getUseStatements($fileNode);
        foreach ($useStatements as $useNode) {
            $useName = $useNode->name->toString();

            if ($className === $useName) {
                return $useName;
            }
        }

        // If not found in use statements, prepend current namespace
        $namespace = $this->getNamespace($fileNode);
        if ($namespace) {
            return $namespace . '\\' . $className;
        }

        return $className;
    }

    /**
     * Check if a class instantiation is allowed
     */
    private function isAllowedInstantiation(string $className): bool
    {
        // Allow all PHP built-in classes (those without namespace)
        if ($this->isPhpBuiltInClass($className)) {
            return true;
        }

        // Check against default allowed classes
        foreach (self::ALLOWED_INSTANTIATIONS as $allowedClass) {
            if ($className === $allowedClass || str_ends_with($className, '\\' . $allowedClass)) {
                return true;
            }
        }

        // Check against additional allowed classes from config
        foreach ($this->additionalAllowedClasses as $allowedClass) {
            if ($className === $allowedClass || str_ends_with($className, '\\' . $allowedClass)) {
                return true;
            }
        }

        // Allow instantiation of classes that end with common value object suffixes
        $valueObjectSuffixes = ['DTO', 'ValueObject', 'Value', 'Data', 'Request', 'Response', 'Event'];
        foreach ($valueObjectSuffixes as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a class is a PHP built-in class
     */
    private function isPhpBuiltInClass(string $className): bool
    {
        // PHP built-in classes don't have namespaces
        if (strpos($className, '\\') !== false) {
            return false;
        }

        // Check if it's a built-in class using PHPStan reflection
        try {
            if ($this->reflectionProvider->hasClass($className)) {
                $reflection = $this->reflectionProvider->getClass($className);

                return $reflection->isBuiltin();
            }
        } catch (Exception $e) {
            // If we can't reflect on it, assume it's not built-in
            return false;
        }

        return false;
    }

    /**
     * Check if the instantiation is of self or parent class
     */
    private function isSelfOrParentInstantiation(string $className): bool
    {
        return in_array(strtolower($className), ['self', 'parent', 'static'], true);
    }
}
