<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that enforces ULID identity on Eloquent domain entities.
 *
 * The trait Illuminate\Database\Eloquent\Concerns\HasUlids must be present
 * on the class itself OR on any ancestor in its inheritance chain. The
 * rule walks every TraitUse statement in the class body — not just the
 * first — and inspects parents via ClassReflection.
 *
 * If the trait is present but the class explicitly disables it via the
 * $incrementing or $keyType property overrides, a distinct error is
 * emitted that names the offending properties so the developer can
 * remove them.
 */
class EnforceUlidsRule extends DomainRule
{
    private const HAS_ULIDS_FQCN = HasUlids::class;

    private const MISSING_TRAIT_MESSAGE = 'Model class "%s" must use the "HasUlids" trait to ensure '.
        'consistent ID handling with ULIDs.';

    private const DISABLED_TRAIT_MESSAGE = 'Model class "%s" uses the "HasUlids" trait but explicitly '.
        'disables it via property overrides ($incrementing or $keyType). Remove these overrides so '.
        'ULID identity remains consistent.';

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        if (! $this->isEloquentModel($node)) {
            return [];
        }

        $classNode = $this->getRootNode($node);
        if (! $classNode instanceof Class_) {
            return [];
        }

        $reflection = $this->getClassReflection($node);
        $hasUlids = $reflection instanceof ClassReflection
            && $this->hasUlidsInChain($reflection);

        if (! $hasUlids) {
            return [$this->buildError(self::MISSING_TRAIT_MESSAGE, $classNode)];
        }

        if ($this->disablesUlid($classNode)) {
            return [$this->buildError(self::DISABLED_TRAIT_MESSAGE, $classNode)];
        }

        return [];
    }

    /**
     * Walk the class itself and every ancestor; return true on first
     * direct usage of HasUlids in the chain.
     */
    private function hasUlidsInChain(ClassReflection $reflection): bool
    {
        $chain = array_merge([$reflection], $reflection->getParents());
        foreach ($chain as $current) {
            if (in_array(self::HAS_ULIDS_FQCN, $current->getNativeReflection()->getTraitNames(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect property overrides that neutralise the HasUlids trait:
     *   - $incrementing = true
     *   - $keyType = anything other than 'string'
     */
    private function disablesUlid(Class_ $classNode): bool
    {
        foreach ($classNode->stmts as $stmt) {
            if (! $stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $name = $prop->name->toString();
                $default = $prop->default;

                if ($name === 'incrementing' &&
                    $default instanceof ConstFetch &&
                    strtolower($default->name->toString()) === 'true') {
                    return true;
                }

                if ($name === 'keyType' &&
                    $default instanceof String_ &&
                    $default->value !== 'string') {
                    return true;
                }
            }
        }

        return false;
    }

    private function buildError(string $template, Class_ $classNode): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            $template,
            $classNode->namespacedName?->toString() ?? 'Unknown'
        ))
            ->line($classNode->getLine())
            ->identifier('ddd.entities.enforceUlids')
            ->build();
    }
}
