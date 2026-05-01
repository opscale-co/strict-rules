<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\DomainServices;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * Rule that flags classes outside `\Services\*` whose method bodies operate
 * on more than two distinct Eloquent model classes.
 *
 * Detection is operation-based, not import-based:
 *   - StaticCall whose `class` is an Eloquent model FQCN (User::find(...))
 *   - New_ whose `class` is an Eloquent model FQCN (new User(...))
 *   - MethodCall ->save() whose receiver is a parameter typed as an Eloquent model
 *
 * Mere references (Model::class, type hints, return types, instanceof) are
 * not operations and are not counted. This means Form Requests, Nova
 * Resources, DTOs and similar declarative classes can reference many
 * models without tripping the rule.
 *
 * Classes under `\Services\*` (including `\Services\Actions\*`) are exempt
 * — `\Services\` is the legitimate home of multi-entity coordination per
 * Article V of the Opscale constitution.
 */
class ComplexLogicRule extends BaseRule
{
    private const PERSISTENCE_METHODS = ['save'];

    private const THRESHOLD = 2;

    /**
     * @return array<int, string>
     */
    public static function persistenceMethods(): array
    {
        return self::PERSISTENCE_METHODS;
    }

    /**
     * Public so the anonymous visitor can call it without losing scope.
     */
    public function isEloquentModelClass(string $className): bool
    {
        try {
            if (! $this->reflectionProvider->hasClass($className)) {
                return false;
            }

            $classReflection = $this->reflectionProvider->getClass($className);
            if ($classReflection->getName() === Model::class) {
                return true;
            }

            return $classReflection->isSubclassOf(Model::class);
        } catch (Throwable) {
            return false;
        }
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        $rootNode = $this->getRootNode($node);
        if ($rootNode instanceof Enum_) {
            return false;
        }

        $namespace = $this->getNamespace($node);
        if ($this->isInNamespaces($namespace, ['\\Services'])) {
            return false;
        }

        return true;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $rootNode = $this->getRootNode($node);
        if (! $rootNode instanceof Class_ && ! $rootNode instanceof Trait_) {
            return [];
        }

        $models = $this->collectOperatedModels($rootNode);
        if (count($models) <= self::THRESHOLD) {
            return [];
        }

        $sorted = $models;
        sort($sorted);
        $error = sprintf(
            'Class "%s" performs operations on %d distinct Eloquent models (%s). '.
            'Complex logic involving more than %d entities must live in a class under '.
            '"\\Services\\" (typically an Opscale Action under "\\Services\\Actions\\").',
            $rootNode->namespacedName?->toString() ?? 'Unknown',
            count($sorted),
            implode(', ', $sorted),
            self::THRESHOLD
        );

        return [
            RuleErrorBuilder::message($error)
                ->line($rootNode->getLine())
                ->identifier('ddd.domainServices.complexLogic')
                ->build(),
        ];
    }

    /**
     * Walk every method body once and collect the FQCNs of Eloquent models
     * on which the class operates.
     *
     * @return array<int, string> distinct FQCNs (insertion-ordered)
     */
    private function collectOperatedModels(Class_|Trait_ $rootNode): array
    {
        $models = [];

        foreach ($this->getMethodNodes($rootNode) as $method) {
            $paramTypes = $this->indexParamTypes($method);
            $this->walkMethodBody($method, $paramTypes, $models);
        }

        return array_keys($models);
    }

    /**
     * @return array<string, string> param-name → FQCN
     */
    private function indexParamTypes(ClassMethod $method): array
    {
        $map = [];
        foreach ($method->params as $param) {
            if (! $param->type instanceof Name) {
                continue;
            }
            if (! isset($param->var->name) || ! is_string($param->var->name)) {
                continue;
            }
            $map[$param->var->name] = $param->type->toString();
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $paramTypes
     * @param  array<string, true>  $models  Updated by reference.
     */
    private function walkMethodBody(ClassMethod $method, array $paramTypes, array &$models): void
    {
        $stmts = $method->stmts ?? [];
        if ($stmts === []) {
            return;
        }

        $rule = $this;
        $visitor = new class($rule, $paramTypes, $models) extends NodeVisitorAbstract
        {
            /** @var array<string, true> */
            public array $models;

            /** @var array<string, string> */
            private array $paramTypes;

            private ComplexLogicRule $rule;

            /**
             * @param  array<string, string>  $paramTypes
             * @param  array<string, true>  $models
             */
            public function __construct(ComplexLogicRule $rule, array $paramTypes, array $models)
            {
                $this->rule = $rule;
                $this->paramTypes = $paramTypes;
                $this->models = $models;
            }

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof StaticCall && $node->class instanceof Name) {
                    $this->record($node->class->toString());
                }
                if ($node instanceof New_ && $node->class instanceof Name) {
                    $this->record($node->class->toString());
                }
                if ($node instanceof MethodCall &&
                    $node->name instanceof Identifier &&
                    in_array($node->name->toString(), ComplexLogicRule::persistenceMethods(), true) &&
                    $node->var instanceof Variable &&
                    is_string($node->var->name) &&
                    isset($this->paramTypes[$node->var->name])) {
                    $this->record($this->paramTypes[$node->var->name]);
                }

                return null;
            }

            private function record(string $fqcn): void
            {
                if ($this->rule->isEloquentModelClass($fqcn)) {
                    $this->models[$fqcn] = true;
                }
            }
        };

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        $models = $visitor->models;
    }
}
