<?php

declare(strict_types=1);

namespace Opscale\Rules\Smells;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that flags Laravel global helper-function usage. Helpers obscure
 * dependencies; the constitution requires explicit DI or facade usage.
 *
 * Detects two shapes:
 *  - chained calls whose receiver is a helper FuncCall:
 *    `auth()->user()`, `cache()->get('key')`
 *  - standalone helper calls: `config('app.name')`
 *
 * Walks every classlike (Class_, Trait_, Enum_) in the file so
 * multi-class layouts are fully covered.
 */
class HelpersRestrictionRule extends BaseRule
{
    private const COMMON_HELPERS = [
        'auth',
        'cache',
        'config',
        'session',
        'request',
        'response',
        'route',
        'url',
        'view',
        'app',
        'collect',
        'logger',
        'storage',
        'validator',
        'cookie',
        'redirect',
        'back',
        'old',
        'csrf_token',
        'csrf_field',
        'method_field',
        'trans',
        '__',
        'trans_choice',
        'policy',
        'rescue',
        'retry',
        'tap',
        'throw_if',
        'throw_unless',
        'with',
        'broadcast',
        'dispatch',
        'event',
        'factory',
        'info',
        'logs',
        'now',
        'optional',
        'report',
        'resolve',
        'today',
        'yesterday',
    ];

    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];
        $nodeFinder = new NodeFinder;

        foreach ($this->getClassLikeNodes($node) as $classNode) {
            $className = $classNode->namespacedName?->toString() ?? 'Unknown';

            foreach ($this->getMethodNodes($classNode) as $method) {
                /** @var list<MethodCall> $methodCalls */
                $methodCalls = $nodeFinder->findInstanceOf($method->stmts ?? [], MethodCall::class);

                foreach ($methodCalls as $methodCall) {
                    if ($methodCall->var instanceof FuncCall && $this->isHelperFunctionCall($methodCall->var)) {
                        $helperName = $methodCall->var->name->toString();
                        $methodName = $methodCall->name instanceof Node\Identifier
                            ? $methodCall->name->toString()
                            : 'unknown';

                        $errors[] = $this->buildError(
                            $methodCall->getLine(),
                            sprintf(
                                'Helper function "%s()->%s()" usage detected in "%s". '.
                                'Consider injecting the service directly instead of using helper functions.',
                                $helperName,
                                $methodName,
                                $className
                            )
                        );
                    }
                }

                /** @var list<FuncCall> $funcCalls */
                $funcCalls = $nodeFinder->findInstanceOf($method->stmts ?? [], FuncCall::class);

                foreach ($funcCalls as $funcCall) {
                    if (! $this->isHelperFunctionCall($funcCall)) {
                        continue;
                    }
                    if ($this->isPartOfMethodChain($funcCall, $methodCalls)) {
                        continue;
                    }

                    $helperName = $funcCall->name->toString();
                    $errors[] = $this->buildError(
                        $funcCall->getLine(),
                        sprintf(
                            'Helper function "%s()" usage detected in "%s". '.
                            'Consider injecting the service directly instead of using helper functions.',
                            $helperName,
                            $className
                        )
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * @param  list<MethodCall>  $methodCalls
     */
    private function isPartOfMethodChain(FuncCall $funcCall, array $methodCalls): bool
    {
        foreach ($methodCalls as $methodCall) {
            if ($methodCall->var === $funcCall) {
                return true;
            }
        }

        return false;
    }

    private function isHelperFunctionCall(FuncCall $funcCall): bool
    {
        if (! $funcCall->name instanceof Node\Name) {
            return false;
        }

        return in_array($funcCall->name->toString(), self::COMMON_HELPERS, true);
    }

    private function buildError(int $line, string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->line($line)
            ->identifier('smells.helpersRestriction.helper')
            ->build();
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
