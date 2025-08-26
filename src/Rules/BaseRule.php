<?php

namespace Opscale\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use Throwable;

/**
 * Base rule with support methods
 */
abstract class BaseRule implements Rule
{
    protected \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * Check if the rule should process the given FileNode
     */
    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! ($node instanceof FileNode)) {
            return false;
        }

        $classReflection = $this->getClassReflection($node);
        if (! $classReflection ||
            $classReflection->isAnonymous() ||
            $classReflection->isInterface()) {
            return false;
        }

        return true;
    }

    /**
     * Resolve the root Class_, Trait_, or Enum_ node from the file
     */
    protected function getRootNode(FileNode|array $node): Class_|Trait_|Enum_|null
    {
        $rootNode = null;
        $namespace = $this->getNamespaceNode($node);
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Class_ ||
                $stmt instanceof Trait_ ||
                $stmt instanceof Enum_) {
                $rootNode = $stmt;
                break;
            }
        }

        return $rootNode;
    }

    /**
     * Resolve a ClassReflection for the root Class_ node
     */
    protected function getClassReflection(FileNode $fileNode): ?ClassReflection
    {
        $classNode = $this->getRootNode($fileNode);
        if ($classNode === null) {
            return null;
        }

        $fqcn = $classNode->namespacedName->toString();
        if (! $this->reflectionProvider->hasClass($fqcn)) {
            return null;
        }

        return $this->reflectionProvider->getClass($fqcn);
    }

    /**
     * Get the namespace name
     */
    protected function getNamespace(FileNode $fileNode): string
    {
        $namespace = $this->getNamespaceNode($fileNode);

        return $namespace instanceof \PhpParser\Node\Stmt\Namespace_ && $namespace->name instanceof \PhpParser\Node\Name
            ? $namespace->name->toString()
            : '';
    }

    /**
     * Extract the Namespace_ statement
     */
    protected function getNamespaceNode(FileNode|array $node): ?Namespace_
    {
        $nodes = is_array($node) ? $node : $node->getNodes();
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get all class declarations within the namespace
     *
     * @return Class_[]
     */
    protected function getClassNodes(FileNode $fileNode): array
    {
        $classes = [];
        $namespace = $this->getNamespaceNode($fileNode);
        if (! $namespace instanceof \PhpParser\Node\Stmt\Namespace_) {
            return $classes;
        }

        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Class_) {
                $classes[] = $stmt;
            }
        }

        return $classes;
    }

    /**
     * Get all use statements within the namespace
     */
    protected function getUseStatements(FileNode $fileNode): array
    {
        $uses = [];
        $namespace = $this->getNamespaceNode($fileNode);
        if (! $namespace instanceof \PhpParser\Node\Stmt\Namespace_) {
            return $uses;
        }

        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $useUse) {
                    $uses[] = $useUse;
                }
            }
        }

        return $uses;
    }

    /**
     * Get the parent class
     */
    protected function getParentNode(FileNode $fileNode): ?string
    {
        $rootClassNode = $this->getRootNode($fileNode);

        if ($rootClassNode === null || ! ($rootClassNode instanceof Class_)) {
            return null;
        }

        if ($rootClassNode->extends === null) {
            return null;
        }

        return $rootClassNode->extends->toString();
    }

    /**
     * Get all methods from a class, trait, or enum node
     */
    protected function getMethodNodes(Class_|Trait_|Enum_ $rootNode): array
    {
        $methods = [];
        foreach ($rootNode->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $methods[] = $stmt;
            }
        }

        return $methods;
    }

    /**
     * Get all used traits in a class
     */
    protected function getTraitNodes(Class_ $class): array
    {
        $traits = [];
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof \PhpParser\Node\Stmt\TraitUse) {
                $traits[] = $stmt;
            }
        }

        return $traits;
    }

    /**
     * Get all interface implementations in a class or enum
     */
    protected function getInterfaceNodes(Class_|Enum_ $class): array
    {
        $interfaces = [];
        foreach ($class->implements as $interface) {
            $interfaces[] = $interface->toString();
        }

        return $interfaces;
    }

    /**
     * Check if a class is in the allowed namespaces list (considering root namespace)
     */
    protected function isInNamespaces(string $namespace, array $allowedNamespaces): bool
    {
        foreach ($allowedNamespaces as $allowedNamespace) {
            // If the allowed namespace starts with a backslash, it's relative to the root
            if (str_starts_with($allowedNamespace, '\\')) {
                $allowedNamespace = preg_quote($allowedNamespace);
                $pattern = '/^(\w+)(\\\w+)*(' . $allowedNamespace . ')/';
                if (preg_match($pattern, $namespace) === 1) {
                    return true;
                }
            }
            // Absolute namespace
            elseif (str_starts_with($namespace, $allowedNamespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the AST for a specific class
     */
    protected function getASTForClass(string $className): ?Node
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $filename = $classReflection->getFileName();

        if (! $filename) {
            return null;
        }

        try {
            // Parse the model's source file to analyze its AST
            $parserFactory = new ParserFactory;
            $phpParser = $parserFactory->createForVersion(PhpVersion::fromString('8.2'));
            $sourceCode = file_get_contents($filename);
            $ast = $phpParser->parse($sourceCode);

            // Find the class node in the AST
            $classNode = $this->getRootNode($ast);

            return $classNode;
        } catch (Throwable $throwable) {
            // If we can't parse the file, fall back to false
            return null;
        }
    }
}
