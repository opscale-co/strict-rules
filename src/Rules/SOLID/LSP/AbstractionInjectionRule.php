<?php

namespace Opscale\Rules\SOLID\LSP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies all constructor injections are interfaces (abstractions)
 * Following the Liskov Substitution Principle (LSP) from SOLID principles
 */
class AbstractionInjectionRule extends BaseRule
{
    /**
     * Constructor method name
     */
    private const CONSTRUCTOR_METHOD = '__construct';

    /**
     * Built-in PHP types that are allowed in constructor injection
     */
    private const ALLOWED_SCALAR_TYPES = [
        'string',
        'int',
        'float',
        'bool',
        'array',
        'callable',
        'iterable',
        'object',
        'mixed'
    ];

    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if(parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        $namespace = $this->getNamespace($node);

        return $this->isInNamespaces($namespace, ['\\Services']);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->shouldProcess($node, $scope)) {
            return [];
        }

        $rootNode = $this->getRootNode($node);

        if (!($rootNode instanceof Class_)) {
            return [];
        }

        $constructorMethod = $this->getConstructorMethod($rootNode);
        
        if ($constructorMethod === null) {
            return [];
        }

        $errors = [];
        $classReflection = $this->getClassReflection($node);
        
        foreach ($constructorMethod->params as $param) {
            $error = $this->validateParameter(
                $param, 
                $classReflection->getName());
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Get the constructor method from class node
     */
    private function getConstructorMethod(Class_ $rootNode): ?ClassMethod
    {
        $methods = $this->getMethodNodes($rootNode);
        
        foreach ($methods as $method) {
            if ($method->name->toString() === self::CONSTRUCTOR_METHOD) {
                return $method;
            }
        }
        
        return null;
    }

    /**
     * Validate a constructor parameter to ensure it's an interface
     */
    private function validateParameter(Param $param, string $className): ?RuleError
    {
        $parameterName = $param->var->name ?? 'unknown';
        $typeName = $this->getParameterTypeName($param);
        
        // Allow scalar types and built-in PHP types
        if ($typeName && $this->isAllowedScalarType($typeName)) {
            return null;
        }

        // Check if the type is an interface
        if ($typeName && $this->isInterface($typeName)) {
            return null;
        }

        $error = sprintf(
            'Constructor parameter "%s" in class "%s" should have a type and be an interface (if it is an object). ' .
            'Follow the Liskov Substitution Principle by depending on abstractions, not concretions.',
            $parameterName,
            $className
        );
            
        return RuleErrorBuilder::message($error)
            ->line($param->getLine())
            ->build();
    }

    /**
     * Extract the type name from a parameter
     */
    private function getParameterTypeName(Param $param): ?string
    {
        if ($param->type === null) {
            return null;
        }

        // Handle union types
        if ($param->type instanceof UnionType) {
            // For union types, we'll check the first type for simplicity
            // In a more complex implementation, we might want to check all types
            if (!empty($param->type->types)) {
                return $this->extractTypeName($param->type->types[0]);
            }
            return null;
        }

        return $this->extractTypeName($param->type);
    }

    /**
     * Extract type name from a type node
     */
    private function extractTypeName(Node $typeNode): ?string
    {
        if ($typeNode instanceof Name) {
            return $typeNode->toString();
        }
        
        if ($typeNode instanceof Identifier) {
            return $typeNode->name;
        }

        return null;
    }

    /**
     * Check if a type is an allowed scalar type
     */
    private function isAllowedScalarType(string $typeName): bool
    {
        return in_array(strtolower($typeName), self::ALLOWED_SCALAR_TYPES, true);
    }

    /**
     * Check if a class/type is an interface
     */
    private function isInterface(string $typeName): bool
    {
        try {
            if (!$this->reflectionProvider->hasClass($typeName)) {
                return false;
            }

            $classReflection = $this->reflectionProvider->getClass($typeName);
            return $classReflection->isInterface();
        } catch (\Throwable $e) {
            // If we can't determine if it's an interface, assume it's not
            return false;
        }
    }
}