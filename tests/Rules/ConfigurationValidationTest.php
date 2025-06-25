<?php

namespace Opscale\Tests\Rules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfigurationValidationTest extends TestCase
{
    private const NEON_FILES = [
        'rules.clean.neon',
        'rules.ddd.neon',
        'rules.solid.neon',
        'rules.smells.neon',
    ];

    public static function neonFilesProvider(): array
    {
        return array_map(fn ($file): array => [$file], self::NEON_FILES);
    }

    #[Test]
    #[DataProvider('neonFilesProvider')]
    public function neon_configuration_file_exists(string $filename): void
    {
        $filePath = __DIR__ . '/../../' . $filename;

        $this->assertFileExists($filePath, sprintf('Configuration file %s should exist', $filename));
    }

    #[Test]
    #[DataProvider('neonFilesProvider')]
    public function neon_configuration_file_has_valid_syntax(string $filename): void
    {
        $filePath = __DIR__ . '/../../' . $filename;
        $content = file_get_contents($filePath);

        $this->assertNotFalse($content, 'Could not read ' . $filename);
        $this->assertNotEmpty($content, sprintf('Configuration file %s should not be empty', $filename));

        // Parse NEON content using simple regex validation for basic structure
        $this->assertStringContainsString('services:', $content, sprintf("Configuration file %s should contain 'services:' section", $filename));
        $this->assertStringContainsString('class:', $content, sprintf('Configuration file %s should contain service class definitions', $filename));
        $this->assertStringContainsString('tags:', $content, sprintf('Configuration file %s should contain service tags', $filename));
    }

    #[Test]
    #[DataProvider('neonFilesProvider')]
    public function neon_configuration_file_has_services_section(string $filename): void
    {
        $filePath = __DIR__ . '/../../' . $filename;
        $content = file_get_contents($filePath);

        // Simple validation for services section structure
        $this->assertStringContainsString('services:', $content, sprintf("Configuration file %s should have 'services' section", $filename));

        // Count service entries (lines with "class:")
        $classCount = substr_count($content, 'class:');
        $this->assertGreaterThan(0, $classCount, sprintf('Services section in %s should not be empty', $filename));
    }

    #[Test]
    #[DataProvider('neonFilesProvider')]
    public function neon_configuration_services_have_required_structure(string $filename): void
    {
        $filePath = __DIR__ . '/../../' . $filename;
        $content = file_get_contents($filePath);

        // Extract class names using regex
        preg_match_all('/class:\s+(.+)/', $content, $matches);
        if (empty($matches[1])) {
            $classNames = [];
        } else {
            $classNames = $matches[1];
        }

        $this->assertNotEmpty($classNames, sprintf('Configuration file %s should contain service class definitions', $filename));

        foreach ($classNames as $className) {
            $className = trim($className);
            $this->assertStringStartsWith('Opscale\\Rules\\', $className,
                sprintf("Service class %s in %s should start with 'Opscale\\Rules\\'", $className, $filename));
        }

        // Validate that phpstan.rules.rule tag is present
        $this->assertStringContainsString('phpstan.rules.rule', $content,
            sprintf("Configuration file %s should contain 'phpstan.rules.rule' tag", $filename));
    }

    #[Test]
    public function all_neon_files_are_included_in_composer_json(): void
    {
        $composerPath = __DIR__ . '/../../composer.json';
        $composerContent = json_decode(file_get_contents($composerPath), true);

        $this->assertArrayHasKey('extra', $composerContent, "composer.json should have 'extra' section");
        $this->assertArrayHasKey('phpstan', $composerContent['extra'], "composer.json extra section should have 'phpstan' key");
        $this->assertArrayHasKey('includes', $composerContent['extra']['phpstan'], "PHPStan section should have 'includes' key");

        $includes = $composerContent['extra']['phpstan']['includes'];

        foreach (self::NEON_FILES as $neonFile) {
            $this->assertContains($neonFile, $includes,
                sprintf('Configuration file %s should be included in composer.json phpstan.includes', $neonFile));
        }
    }

    #[Test]
    #[DataProvider('neonFilesProvider')]
    public function neon_configuration_classes_exist(string $filename): void
    {
        $filePath = __DIR__ . '/../../' . $filename;
        $content = file_get_contents($filePath);

        // Extract class names using regex
        preg_match_all('/class:\s+(.+)/', $content, $matches);
        if (empty($matches[1])) {
            $classNames = [];
        } else {
            $classNames = $matches[1];
        }

        foreach ($classNames as $className) {
            $className = trim($className);
            $this->assertTrue(class_exists($className),
                sprintf('Rule class %s defined in %s should exist', $className, $filename));
        }
    }
}
