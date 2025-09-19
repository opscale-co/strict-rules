<?php

namespace Opscale\Rules\Smells;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

class HelpersRestrictionRule extends BaseRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];
        $rootNode = $this->getRootNode($node);
        $nodeFinder = new NodeFinder;

        if ($rootNode === null) {
            return $errors;
        }

        $methods = $this->getMethodNodes($rootNode);

        foreach ($methods as $method) {
            // Check for method calls that might be chained from helpers (e.g., cache()->get())
            $methodCalls = $nodeFinder->findInstanceOf($method->stmts ?? [], MethodCall::class);

            foreach ($methodCalls as $methodCall) {
                if ($methodCall->var instanceof FuncCall && $this->isHelperFunctionCall($methodCall->var)) {
                    $helperName = $methodCall->var->name->toString();
                    $methodName = $methodCall->name instanceof Node\Identifier ? $methodCall->name->toString() : 'unknown';

                    $error = sprintf(
                        'Helper function "%s()->%s()" usage detected in "%s". ' .
                        'Consider injecting the service directly instead of using helper functions.',
                        $helperName,
                        $methodName,
                        $rootNode->namespacedName?->toString() ?? 'Unknown'
                    );

                    $errors[] = RuleErrorBuilder::message($error)
                        ->line($methodCall->getLine())
                        ->identifier('smells.helpersRestriction.helper')
                        ->build();
                }
            }

            // Check standalone helper function calls (e.g., config('app.name'))
            $funcCalls = $nodeFinder->findInstanceOf($method->stmts ?? [], FuncCall::class);

            foreach ($funcCalls as $funcCall) {
                if ($this->isHelperFunctionCall($funcCall)) {
                    // Skip if this function call is part of a method chain (already handled above)
                    $isPartOfMethodChain = false;
                    foreach ($methodCalls as $methodCall) {
                        if ($methodCall->var === $funcCall) {
                            $isPartOfMethodChain = true;
                            break;
                        }
                    }

                    if (! $isPartOfMethodChain) {
                        $helperName = $funcCall->name->toString();

                        $error = sprintf(
                            'Helper function "%s()" usage detected in "%s". ' .
                            'Consider injecting the service directly instead of using helper functions.',
                            $helperName,
                            $rootNode->namespacedName?->toString() ?? 'Unknown'
                        );

                        $errors[] = RuleErrorBuilder::message($error)
                            ->line($funcCall->getLine())
                            ->identifier('smells.helpersRestriction.helper')
                            ->build();
                    }
                }
            }
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        return true;
    }

    private function isHelperFunctionCall(FuncCall $funcCall): bool
    {
        if (! ($funcCall->name instanceof Node\Name)) {
            return false;
        }

        $functionName = $funcCall->name->toString();

        $commonHelpers = [
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

        return in_array($functionName, $commonHelpers, true);
    }
}
