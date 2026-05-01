<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\Domain\Helpers\EntityCountCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that limits the number of concrete Eloquent entities per
 * subdomain (= per file-level namespace).
 *
 * Implemented as a `Rule<CollectedDataNode>` paired with
 * `EntityCountCollector`. The collector records every concrete
 * Eloquent model in any analysed file; the rule groups those records
 * by namespace, deduplicates by FQCN, and emits one error per
 * subdomain whose unique-fqcn count exceeds `maxClasses`.
 *
 * @implements Rule<CollectedDataNode>
 */
class EntityCountRule implements Rule
{
    public const DEFAULT_MAX_CLASSES = 25;

    public function __construct(private readonly int $maxClasses = self::DEFAULT_MAX_CLASSES) {}

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var array<string, list<list<array{namespace: string, fqcn: string, file: string}>>> $perFile */
        $perFile = $node->get(EntityCountCollector::class);

        $fqcnsBySubdomain = [];
        $filesBySubdomain = [];
        foreach ($perFile as $batches) {
            foreach ($batches as $records) {
                foreach ($records as $record) {
                    $namespace = $record['namespace'];
                    $fqcnsBySubdomain[$namespace][] = $record['fqcn'];
                    $filesBySubdomain[$namespace][] = $record['file'];
                }
            }
        }

        $errors = [];
        foreach ($fqcnsBySubdomain as $namespace => $fqcns) {
            $unique = array_values(array_unique($fqcns));
            $count = count($unique);
            if ($count <= $this->maxClasses) {
                continue;
            }

            $error = sprintf(
                'Subdomain "%s" has %d entities, which exceeds the maximum of %d entities. '.
                'Consider splitting this subdomain into smaller, more focused subdomains.',
                $namespace,
                $count,
                $this->maxClasses
            );

            $firstFile = $filesBySubdomain[$namespace][0];
            $errors[] = RuleErrorBuilder::message($error)
                ->file($firstFile)
                ->line(1)
                ->identifier('ddd.subdomains.entityCount')
                ->build();
        }

        return $errors;
    }
}
