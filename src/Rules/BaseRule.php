<?php

namespace Opscale\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ClassReflection;

/**
 * Base rule with support methods
 */
abstract class BaseRule implements Rule
{
    /**
     * @var ReflectionProvider
     */
    protected $reflectionProvider;

    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        $classReflection = $this->getClassReflection($node);
        if (!$classReflection ||
            $classReflection->isAnonymous() || 
            $classReflection->isInterface()) {
            return false;
        }
        
        return true;
    }

    /**
     * Resolve the root Class_ node from the file
     *
     * @param FileNode $node
     * @return bool
     */
    protected function getRootNode(FileNode|array $node): mixed
    {
        $rootNode = null;
        $namespace = $this->getNamespaceNode($node);
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Class_ ||
                $stmt instanceof Trait_) {
                $rootNode = $stmt;
            }
        }

        return $rootNode;
    }

    /**
     * Resolve a ClassReflection for the root Class_ node 
     *
     * @param FileNode $node
     * @return ClassReflection|null
     */
    protected function getClassReflection(FileNode $node): ?ClassReflection
    {
        $classNode = $this->getRootNode($node);
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
        return $namespace !== null && $namespace->name !== null
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
     * @param FileNode $node
     * @return Class_[]
     */
    protected function getClassNodes(FileNode $node): array
    {
        $classes = [];
        $namespace = $this->getNamespaceNode($node);
        if ($namespace === null) {
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
     *
     * @param FileNode $node
     * @return string[] Fully qualified names
     */
    protected function getUseStatements(FileNode $node): array
    {
        $uses = [];
        $namespace = $this->getNamespaceNode($node);
        if ($namespace === null) {
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
     *
     * @param FileNode $classNode
     * @return string Parent class name
     */
    protected function getParentNode(FileNode $node): string
    {
        $rootClassNode = $this->getRootNode($node);
        $parentClass = $rootClassNode->extends->toString();

        return $parentClass;
    }

    /**
     * Get all methods from a class node
     *
     * @param Node $rootNode
     * @return \PhpParser\Node\Stmt\ClassMethod[]
     */
    protected function getMethodNodes(Node $rootNode): array
    {
        return $rootNode->getMethods();
    }

    /**
     * Get all used traits in a class
     *
     * @param Class_ $classNode
     * @return \PhpParser\Node\Stmt\TraitUse[]
     */
    protected function getTraitNodes(Class_ $classNode): array
    {
        $traits = [];
        foreach ($classNode->stmts as $stmt) {
            if ($stmt instanceof \PhpParser\Node\Stmt\TraitUse) {
                $traits[] = $stmt;
            }
        }
        return $traits;
    }

    /**
     * Get all interface implementations in a class
     *
     * @param Node $rootNode
     * @return string[] Interface names
     */
    protected function getInterfaceNodes(Node $rootNode): array
    {
        $interfaces = [];
        foreach ($rootNode->implements as $interface) {
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
            else if (str_starts_with($namespace, $allowedNamespace)) {
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
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $filename = $classReflection->getFileName();
        
        if (!$filename) {
            return null;
        }

        try {
            // Parse the model's source file to analyze its AST
            $parser = new ParserFactory();
            $phpParser = $parser->createForVersion(PhpVersion::fromString('8.2'));
            $sourceCode = file_get_contents($filename);
            $ast = $phpParser->parse($sourceCode);
            
            // Find the class node in the AST
            $classNode = $this->getRootNode($ast);
            
            return $classNode;
        } catch (\Throwable $e) {
            // If we can't parse the file, fall back to false
            return null;
        }
    }
}