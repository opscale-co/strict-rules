<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Set\LaravelSetList;

/**
 * PHP Setup - Basic configuration for PHP projects
 */
function phpSetup(RectorConfig $rectorConfig): void
{
    $rectorConfig->paths([
        // Basic library/package
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        // Tests fixtures
        __DIR__ . '/tests/fixtures',

        // Specific rules
        Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector::class,
    ]);

    $rectorConfig->sets([
        // Basic PHP rules
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,

        // PHPUnit rules
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
}

/**
 * Laravel Setup - Extends PHP Setup with Laravel-specific configuration
 */
function laravelSetup(RectorConfig $rectorConfig): void
{
    // Apply base PHP configuration
    phpSetup($rectorConfig);

    // Add Laravel-specific paths
    $currentPaths = $rectorConfig->paths();
    $rectorConfig->paths(array_merge($currentPaths, [
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
    ]));

    // Add Laravel-specific sets
    $currentSets = $rectorConfig->sets();
    $rectorConfig->sets(array_merge($currentSets, [
        // Laravel specific rules
        LaravelSetList::LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    ]));
}

/**
 * Nova Setup - Extends Laravel Setup with Laravel Nova-specific configuration
 */
function novaSetup(RectorConfig $rectorConfig): void
{
    // Apply base Laravel configuration (which includes PHP)
    laravelSetup($rectorConfig);

    // Add Nova-specific paths
    $currentPaths = $rectorConfig->paths();
    $rectorConfig->paths(array_merge($currentPaths, [
        __DIR__ . '/nova-components',
    ]));
}

// Default configuration - use phpSetup for basic projects
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheDirectory('/tmp/rector-cache');
    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->phpVersion(PhpVersion::PHP_82);

    // For basic PHP projects/libraries:
    phpSetup($rectorConfig);

    // For Laravel projects:
    // laravelSetup($rectorConfig);

    // For Laravel Nova projects:
    // novaSetup($rectorConfig);
};
