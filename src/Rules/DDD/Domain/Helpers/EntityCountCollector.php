<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Domain\Helpers;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Records every concrete Eloquent model declared in any analysed file.
 *
 * Paired with EntityCountRule, which aggregates the records by
 * file-level namespace and emits one error per subdomain whose count
 * exceeds the configured threshold.
 *
 * @implements Collector<FileNode, array{namespace: string, fqcn: string, file: string}>
 */
class EntityCountCollector implements Collector
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @return list<array{namespace: string, fqcn: string, file: string}>|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $records = [];
        foreach ($this->getClassNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }
            if ($classNode->isAbstract()) {
                continue;
            }

            $fqcn = $classNode->namespacedName->toString();
            if (! $this->isConcreteEloquentModel($fqcn)) {
                continue;
            }

            $records[] = [
                'namespace' => $this->getNamespace($node),
                'fqcn' => $fqcn,
                'file' => $scope->getFile(),
            ];
        }

        return $records !== [] ? $records : null;
    }

    /**
     * @return list<Class_>
     */
    private function getClassNodes(FileNode $fileNode): array
    {
        $classes = [];
        foreach ($fileNode->getNodes() as $stmt) {
            if (! $stmt instanceof Namespace_) {
                continue;
            }
            foreach ($stmt->stmts as $inner) {
                if ($inner instanceof Class_) {
                    $classes[] = $inner;
                }
            }
        }

        return $classes;
    }

    private function getNamespace(FileNode $fileNode): string
    {
        foreach ($fileNode->getNodes() as $stmt) {
            if ($stmt instanceof Namespace_ && $stmt->name instanceof Name) {
                return $stmt->name->toString();
            }
        }

        return '';
    }

    private function isConcreteEloquentModel(string $fqcn): bool
    {
        if (! $this->reflectionProvider->hasClass($fqcn)) {
            return false;
        }

        $reflection = $this->reflectionProvider->getClass($fqcn);
        if ($reflection->isAbstract() ||
            $reflection->isInterface() ||
            $reflection->isTrait() ||
            $reflection->isEnum() ||
            $reflection->isAnonymous()) {
            return false;
        }

        if ($reflection->getName() === Model::class) {
            return true;
        }

        return $reflection->isSubclassOf(Model::class);
    }
}
