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

/**
 * Abstract rule that enforces Clean Architecture layer dependencies
 * Only allows usage of classes from previous/lower layers
 * also from specific Facades related to the layer
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
        ],
        3 => [ // Transformation Layer
            '\\Services\\',
            '\\Exceptions\\',
            '\\Contracts\\',
        ],
        4 => [ // Orchestration Layer
            '\\Jobs\\',
            '\\Notifications\\',
        ],
        5 => [ // Interaction Layer
            '\\Console\\',
            '\\Http\\',
            '\\Nova\\',
            '\\Policies\\',
        ],
    ];

    protected int $processingLayer;

    protected array $allowedFrameworkImports;

    protected array $allowedFacades;

    protected array $allowedExternalImports;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        ?int $processingLayer = null,
        array $allowedFrameworkImports = [],
        array $allowedFacades = [],
        array $allowedExternalImports = []
    ) {
        parent::__construct($reflectionProvider);
        $this->processingLayer = $processingLayer ?? $this->processingLayer();
        $this->allowedFrameworkImports = $allowedFrameworkImports ?: $this->getAllowedFrameworkImports();
        $this->allowedFacades = $allowedFacades ?: $this->getAllowedFacades();
        $this->allowedExternalImports = $allowedExternalImports ?: $this->getAllowedExternalImports();
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

        foreach ($uses as $use) {
            $usedClass = $use->name->toString();
            $usedLayer = $this->getClassLayer($usedClass);

            $error = null;

            // Check if it's a layer dependency violation (higher layer depending on lower layer)
            if ($usedLayer != null && ! $this->isAllowedLayer($node, $use)) {
                $error = sprintf(
                    'Clean Architecture violation: Class "%s" from layer %d cannot depend on "%s" from layer %d. ' .
                    'Layers can only use equal or lower layers and communicate via events upwards.',
                    $className,
                    $classLayer,
                    $usedClass,
                    $usedLayer
                );
            }
            // Check if it's an external dependency that needs validation
            elseif ($usedLayer == null && ! $this->isAllowedUse($node, $use)) {
                $error = sprintf(
                    'Clean Architecture violation: Class "%s" from layer %d cannot depend on "%s". ' .
                    'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                    $className,
                    $classLayer,
                    $usedClass
                );
            }

            if ($error != null) {
                $errors[] = RuleErrorBuilder::message($error)
                    ->line($use->getLine())
                    ->identifier('clean.layer' . $classLayer . '.importNotAllowed')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Check if the use statement is allowed based on all 4 import types
     * Evaluates: Facades, Framework imports, Project imports, and External imports
     */
    public function isAllowedUse(FileNode $fileNode, UseItem $useItem): bool
    {
        return $this->isAllowedFacade($fileNode, $useItem) ||
               $this->isAllowedFrameworkUse($fileNode, $useItem) ||
               $this->isAllowedProjectUse($fileNode, $useItem) ||
               $this->isAllowedExternalUse($fileNode, $useItem);
    }

    /**
     * Check if the class is a Facade and if it is allowed in the current layer
     */
    protected function isAllowedFacade(FileNode $fileNode, UseItem $useItem): bool
    {
        $usedClass = $useItem->name->toString();

        $isFacade = str_starts_with($usedClass, 'Illuminate\\Support\\Facades\\');
        $facade = substr($usedClass, strlen('Illuminate\\Support\\Facades\\'));

        return $isFacade && in_array($facade, $this->allowedFacades);
    }

    /**
     * Check if the class is a Framework import and if it is allowed in the current layer
     */
    protected function isAllowedFrameworkUse(FileNode $fileNode, UseItem $useItem): bool
    {
        $usedClass = $useItem->name->toString();

        foreach ($this->allowedFrameworkImports as $allowedImport) {
            if (str_starts_with($usedClass, $allowedImport)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the class is a Project import and if it is allowed in the current layer
     * Project imports are allowed if they belong to the same layer or lower layers
     */
    protected function isAllowedProjectUse(FileNode $fileNode, UseItem $useItem): bool
    {
        $usedClass = $useItem->name->toString();

        // Get the layer of the used class
        $usedLayer = $this->getClassLayer($usedClass);

        // If the class is not in any defined layer, it's not a project import
        if ($usedLayer === null) {
            return false;
        }

        // Allow project imports from the same layer or lower layers
        return $usedLayer <= $this->processingLayer;
    }

    /**
     * Check if the class is an External import and if it is allowed in the current layer
     */
    protected function isAllowedExternalUse(FileNode $fileNode, UseItem $useItem): bool
    {
        $usedClass = $useItem->name->toString();

        foreach ($this->allowedExternalImports as $allowedImport) {
            if (str_starts_with($usedClass, $allowedImport)) {
                return true;
            }
        }

        return false;
    }

    protected function processingLayer(): int
    {
        return $this->processingLayer;
    }

    protected function getAllowedFrameworkImports(): array
    {
        return [];
    }

    protected function getAllowedFacades(): array
    {
        return [];
    }

    protected function getAllowedExternalImports(): array
    {
        return [];
    }

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

        return $classLayer !== null && $classLayer === $this->processingLayer;
    }

    /**
     * Check if the use statement is allowed in the current layer
     */
    protected function isAllowedLayer(FileNode $fileNode, UseItem $useItem): bool
    {
        $usedClass = $useItem->name->toString();
        $usedLayer = $this->getClassLayer($usedClass);
        if ($usedLayer === null) {
            return true; // Class is not in a defined layer
        }

        $rootNode = $this->getRootNode($fileNode);
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
}
