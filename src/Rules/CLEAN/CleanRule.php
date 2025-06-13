<?php

namespace Opscale\Rules\CLEAN;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\UseItem;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * Abstract rule that enforces Clean Architecture layer dependencies
 * Only allows usage of classes from previous/lower layers
 */
abstract class CleanRule extends BaseRule
{
    /**
     * Layer definitions with their corresponding folders
     * Lower number = lower layer (can be used by higher layers)
     */
    protected const LAYERS = [
        1 => [ // Representation Layer
            '\\Models\\',
        ],
        2 => [ // Communication Layer
            '\\Observers\\',
            '\\Events\\',
        ],
        3 => [ // Transformation Layer
            '\\Services\\',
            '\\Exceptions\\',
            '\\Contracts\\',
        ],
        4 => [ // Orchestration Layer
            '\\Jobs\\',
            '\\Listeners\\',
            '\\Notifications\\',
        ],
        5 => [ // Interaction Layer
            '\\Console\\',
            '\\Http\\',
            '\\Nova\\',
            '\\Policies\\',
        ],
    ];

    /**
     * Facades that are allowed in each layer
     * These are commonly used Laravel facades that can be used across layers
     */
    protected const FACADES = [
        1 => [ // Representation Layer
            'DB',
            'Hash',
            'Schema',
        ],
        2 => [ // Communication Layer
            'Broadcast',
            'Event',
        ],
        3 => [ // Transformation Layer
            'App',
            'Cache',
            'Config',
            'Crypt',
            'Exceptions',
            'File',
            'Http',
            'Storage',
        ],
        4 => [ // Orchestration Layer
            'Bus',
            'Concurrency',
            'Mail',
            'Notification',
            'Pipeline',
            'Queue',
            'Redis',
            'Schedule',
        ],
        5 => [ // Interaction Layer
            'Artisan',
            'Auth',
            'Blade',
            'Context',
            'Cookie',
            'Gate',
            'Lang',
            'Password',
            'Process',
            'RateLimiter',
            'Redirect',
            'Request',
            'Response',
            'Route',
            'Session',
            'URL',
            'Validator',
            'View',
            'Vite',
        ],
    ];

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    /**
     * Check if the use statement is allowed for the processing
     */
    public function allowUse(UseItem $useNode): bool
    {
        $usedClass = $useNode->name->toString();
        $rootParent = $this->getRootParentNamespace($usedClass);
        if ($rootParent === null) {
            return false; // Class is not in a defined layer
        }

        foreach ($this->getAllowedBaseClasses() as $baseClass) {
            if (str_starts_with($usedClass, $baseClass)) {
                return true; // Class is allowed in this layer
            }
        }

        return false; // Class is not allowed in this layer
    }

    /*
     * @param FileNode $node
     * @param Scope $scope
     * @return IdentifierRuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            ! $this->shouldProcess($node, $scope)) {
            return []; // Skip if not a model class
        }

        $errors = [];
        $uses = $this->getUseStatements($node);
        $rootNode = $this->getRootNode($node);
        $className = $rootNode->namespacedName->toString();
        $classLayer = $this->getClassLayer($className);

        foreach ($uses as $useNode) {
            $usedClass = $useNode->name->toString();
            $usedLayer = $this->getClassLayer($usedClass);

            $error = null;
            if ($usedLayer != null && ! $this->isAllowedLayer($node, $useNode)) {
                $error = sprintf(
                    'Clean Architecture violation: Class "%s" from layer %d cannot depend on "%s" from layer %d. ' .
                    'Layers can only use equal or lower layers and communicate via events upwards.',
                    $className,
                    $classLayer,
                    $usedClass,
                    $usedLayer
                );
            } elseif ($usedLayer == null && ! $this->allowUse($useNode) && ! $this->isAllowedFacade($node, $useNode)) {
                $error = sprintf(
                    'Clean Architecture violation: Class "%s" from layer %d cannot depend on "%s". ' .
                    'This class is not allowed in this layer, it does not comply with the layer purpose.',
                    $className,
                    $classLayer,
                    $usedClass
                );
            }

            if ($error != null) {
                $errors[] = RuleErrorBuilder::message($error)
                    ->line($useNode->getLine())
                    ->identifier('clean.layer' . $classLayer . '.importNotAllowed')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Check if the class is a Facade and if it is allowed in the current layer
     */
    public function isAllowedFacade(FileNode $node, UseItem $useNode): bool
    {
        $rootNode = $this->getRootNode($node);
        $className = $rootNode->namespacedName->toString();
        $classLayer = $this->getClassLayer($className);
        $allowedFacades = self::FACADES[$classLayer] ?? [];
        $usedClass = $useNode->name->toString();

        $isFacade = str_starts_with($usedClass, 'Illuminate\\Support\\Facades\\');
        $facade = substr($usedClass, strlen('Illuminate\\Support\\Facades\\'));

        return $isFacade && in_array($facade, $allowedFacades);
    }

    abstract protected function processingLayer(): int;

    abstract protected function getAllowedBaseClasses(): array;

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        // Only process for processing layer
        $rootNode = $this->getRootNode($node);
        $className = $rootNode->namespacedName->toString();
        $classLayer = $this->getClassLayer($className);

        return $classLayer !== null && $classLayer == $this->processingLayer();
    }

    /**
     * Check if the use statement is allowed in the current layer
     */
    protected function isAllowedLayer(FileNode $node, UseItem $useNode): bool
    {
        $usedClass = $useNode->name->toString();
        $usedLayer = $this->getClassLayer($usedClass);
        if ($usedLayer === null) {
            return true; // Class is not in a defined layer
        }

        $rootNode = $this->getRootNode($node);
        $className = $rootNode->namespacedName->toString();
        $classLayer = $this->getClassLayer($className);

        return $usedLayer != null && $usedLayer <= $classLayer;
    }

    /**
     * Get the layer number for a given class name
     */
    protected function getClassLayer(string $className): ?int
    {
        foreach (self::LAYERS as $layerNumber => $folders) {
            foreach ($folders as $folder) {
                $pattern = '/^(\w+)(\\\w+)*(' . preg_quote($folder) . ')/';
                if (preg_match($pattern, $className)
                    && ! str_starts_with($className, 'Illuminate\\')) {
                    return $layerNumber;
                }
            }
        }

        return null;
    }

    /**
     * Get the root parent class namespace by traversing the inheritance chain
     * For example, if A extends B and B extends C, this returns the namespace of C
     */
    protected function getRootParentNamespace(string $className): ?string
    {
        try {
            if (! $this->reflectionProvider->hasClass($className)) {
                return null;
            }

            $classReflection = $this->reflectionProvider->getClass($className);
            $currentClass = $classReflection;

            // Traverse up the inheritance chain
            while ($parentClass = $currentClass->getParentClass()) {
                $currentClass = $parentClass;
            }

            return $currentClass->getDisplayName();

        } catch (Throwable $e) {
            return null;
        }
    }
}
