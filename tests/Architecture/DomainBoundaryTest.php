<?php

namespace Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DomainBoundaryTest extends TestCase
{
    /** @var array<string, list<string>> */
    private const ALLOWED_DOMAIN_DEPENDENCIES = [
        'Shared' => ['Shared'],
        'PoeCatalog' => ['Shared', 'PoeCatalog'],
        'PolicyProvenance' => ['Shared', 'PolicyProvenance'],
        'Rulesets' => ['Shared', 'PolicyProvenance', 'Rulesets'],
        'BuildIntake' => ['Shared', 'PoeCatalog', 'Rulesets', 'BuildIntake'],
        'Analysis' => ['Shared', 'BuildIntake', 'Rulesets', 'Analysis'],
        'Recommendations' => ['Shared', 'BuildIntake', 'Analysis', 'Recommendations'],
        'TradePlanning' => ['Shared', 'Recommendations', 'TradePlanning'],
        'UsageFunding' => ['Shared', 'PolicyProvenance', 'UsageFunding'],
    ];

    /** @return iterable<string, array{string}> */
    public static function domainFiles(): iterable
    {
        foreach (self::phpFiles(dirname(__DIR__, 2).'/src/Domain') as $file) {
            yield str_replace('\\', '/', $file) => [$file];
        }
    }

    /** @return iterable<string, array{string}> */
    public static function pureSourceFiles(): iterable
    {
        foreach (self::phpFiles(dirname(__DIR__, 2).'/src') as $file) {
            yield str_replace('\\', '/', $file) => [$file];
        }
    }

    #[DataProvider('pureSourceFiles')]
    public function test_framework_independent_source_has_no_delivery_or_provider_dependencies(string $file): void
    {
        self::assertFileIsReadable($file);
        $imports = self::imports($file);
        $forbiddenPrefixes = [
            'App\\',
            'Illuminate\\',
            'Laravel\\',
            'Symfony\\',
            'Inertia\\',
            'OpenAI\\',
            'GuzzleHttp\\',
            'Psr\\Http\\',
        ];

        foreach ($imports as $import) {
            foreach ($forbiddenPrefixes as $prefix) {
                self::assertFalse(
                    str_starts_with($import, $prefix),
                    "{$file} imports forbidden dependency {$import}.",
                );
            }
        }
    }

    #[DataProvider('domainFiles')]
    public function test_domain_never_depends_on_application_or_an_outward_module(string $file): void
    {
        self::assertFileIsReadable($file);
        $namespace = self::namespace($file);
        self::assertMatchesRegularExpression('/^Lootwright\\\\Domain\\\\[^\\\\]+/', $namespace);
        self::assertStringNotContainsString('Lootwright\\Application\\', file_get_contents($file) ?: '');

        preg_match('/^Lootwright\\\\Domain\\\\([^\\\\]+)/', $namespace, $ownerMatch);
        $owner = $ownerMatch[1] ?? '';
        self::assertArrayHasKey($owner, self::ALLOWED_DOMAIN_DEPENDENCIES);

        foreach (self::imports($file) as $import) {
            if (preg_match('/^Lootwright\\\\Domain\\\\([^\\\\]+)/', $import, $dependencyMatch) !== 1) {
                continue;
            }

            $dependency = $dependencyMatch[1];
            self::assertContains(
                $dependency,
                self::ALLOWED_DOMAIN_DEPENDENCIES[$owner],
                "{$owner} may not depend on outward domain module {$dependency} in {$file}.",
            );
        }
    }

    public function test_game_adapter_namespaces_cannot_import_each_other(): void
    {
        $adaptersRoot = dirname(__DIR__, 2).'/src/GameAdapters';

        if (! is_dir($adaptersRoot)) {
            self::assertDirectoryDoesNotExist($adaptersRoot);

            return;
        }

        foreach (self::phpFiles($adaptersRoot) as $file) {
            $content = file_get_contents($file) ?: '';
            $normalized = str_replace('\\', '/', $file);

            if (str_contains($normalized, '/PoE1/')) {
                self::assertStringNotContainsString('GameAdapters\\PoE2\\', $content);
            }

            if (str_contains($normalized, '/PoE2/')) {
                self::assertStringNotContainsString('GameAdapters\\PoE1\\', $content);
            }
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files, SORT_STRING);

        return $files;
    }

    /** @return list<string> */
    private static function imports(string $file): array
    {
        $content = file_get_contents($file) ?: '';
        preg_match_all('/^use\s+([^;]+);/m', $content, $matches);

        return $matches[1];
    }

    private static function namespace(string $file): string
    {
        $content = file_get_contents($file) ?: '';
        preg_match('/^namespace\s+([^;]+);/m', $content, $matches);

        return is_string($matches[1] ?? null) ? $matches[1] : '';
    }
}
