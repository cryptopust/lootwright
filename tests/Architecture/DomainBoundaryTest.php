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

    public function test_pob_delivery_paths_cannot_call_the_ungated_parser_directly(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2).'/app/Http') as $file) {
            $content = file_get_contents($file) ?: '';

            self::assertStringNotContainsString('PobImportCoordinator', $content);
            self::assertStringNotContainsString('Lootwright\\GameAdapters\\', $content);
        }

        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/PobImportController.php') ?: '';
        $command = file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/ImportPobFixture.php') ?: '';

        self::assertStringContainsString('PolicyGatedPobImporter', $controller);
        self::assertStringContainsString('PolicyGatedPobImporter', $command);
        self::assertStringContainsString('LocalFixtureCapabilityPolicy', $command);
        self::assertStringNotContainsString('->import(', $command);
        self::assertStringNotContainsString('->prepare(', $command);
        self::assertStringNotContainsString('->normalize(', $command);

        $allowedCoordinatorConsumers = [
            '/app/Console/Commands/ImportPobFixture.php',
            '/app/Modules/BuildIntake/LocalEvaluationPobImporter.php',
            '/app/Modules/BuildIntake/PolicyGatedPobImporter.php',
            '/app/Providers/AppServiceProvider.php',
        ];

        foreach (self::phpFiles(dirname(__DIR__, 2).'/app') as $file) {
            $content = file_get_contents($file) ?: '';

            if (! str_contains($content, 'PobImportCoordinator')) {
                continue;
            }

            $normalized = str_replace('\\', '/', $file);
            $matched = array_filter(
                $allowedCoordinatorConsumers,
                static fn (string $suffix): bool => str_ends_with($normalized, $suffix),
            );
            self::assertNotSame([], $matched, "{$file} bypasses the policy-gated PoB import use case.");
        }

        $evaluationImporter = file_get_contents(dirname(__DIR__, 2).'/app/Modules/BuildIntake/LocalEvaluationPobImporter.php') ?: '';
        self::assertStringContainsString('PolicyGatedPobImporter', $evaluationImporter);
        self::assertStringContainsString('LocalFixtureCapabilityPolicy', $evaluationImporter);
    }

    public function test_provider_neutral_application_layer_cannot_import_concrete_game_adapters(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2).'/src/Application') as $file) {
            self::assertStringNotContainsString(
                'Lootwright\\GameAdapters\\',
                file_get_contents($file) ?: '',
                "{$file} imports a concrete game adapter.",
            );
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
