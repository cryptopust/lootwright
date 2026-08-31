<?php

namespace App\Console\Commands;

use App\Modules\Analysis\Infrastructure\ProductionPoe1DeterministicAnalysisEngine;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use App\Modules\Release\RuntimeMarker;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use RuntimeException;
use Throwable;

/**
 * Runs a non-destructive PoE1 canonical smoke path.  It deliberately avoids
 * persistence, queues, external providers, and fixture bindings.
 */
final class AcceptPoe1 extends Command
{
    protected $signature = 'lootwright:acceptance:poe1 {--file= : Absolute path to a non-sensitive PoB XML (defaults to the bundled acceptance build)}';

    protected $description = 'Execute the production-canonical PoE1 parser, analyzer, planner, and manual recipe path';

    public function handle(PolicyGatedPobImporter $importer, ProductionPoe1DeterministicAnalysisEngine $engine): int
    {
        $started = hrtime(true);

        try {
            RuntimeMarker::assertCanonical();
            if (app()->environment(['local', 'testing'])) {
                throw new RuntimeException('PoE1 acceptance requires a deployed/staging environment; local and testing runtimes are refused.');
            }

            $path = $this->option('file');
            $path = is_string($path) && $path !== ''
                ? $path
                : (string) config('acceptance.poe1_build', base_path('resources/acceptance/poe1-supported.xml'));
            $resolved = realpath($path);
            if (! is_string($resolved) || ! is_file($resolved) || is_link($path)
                || preg_match('#[\\/]tests[\\/]fixtures[\\/]#i', $resolved) === 1
                || preg_match('/fixture|fake|mock/i', basename($resolved)) === 1
            ) {
                throw new RuntimeException('Acceptance input must be a regular non-fixture PoE1 build file.');
            }
            $xml = file_get_contents($resolved);
            if (! is_string($xml) || $xml === '') {
                throw new RuntimeException('Acceptance input could not be read.');
            }

            $import = $importer->handle($xml, false, expectedEdition: GameEdition::Poe1);
            $normalized = CanonicalJson::encode($import->result);
            $analysisId = (string) Str::uuid7();
            $artifactId = (string) Str::uuid7();
            $analysis = new AnalysisRecord(
                $analysisId,
                $artifactId,
                'acceptance-operator',
                GameEdition::Poe1,
                1,
                AnalysisState::Processing,
                CanonicalJson::encode(['goals' => ['production PoE1 acceptance'], 'selection' => []]),
                hash('sha256', 'production-poe1-acceptance'),
            );
            $artifact = new ArtifactRecord(
                $artifactId,
                'acceptance-operator',
                $analysisId,
                GameEdition::Poe1,
                'pob',
                'acceptance://poe1',
                $import->inputChecksumSha256,
                AnalysisState::Completed,
                'pob1',
                $import->parserVersion,
                $normalized,
                hash('sha256', $normalized),
                '3.29.1',
                null,
            );
            $context = $engine->resolve($analysis, $artifact);
            $snapshot = $engine->run($analysis, $artifact, $context);
            $elapsed = (int) ceil((hrtime(true) - $started) / 1_000_000);
            $findingCodes = array_values(array_map(static fn ($finding): string => $finding->code, $snapshot->findings));
            $recommendationCodes = array_values(array_map(static fn ($recommendation): string => $recommendation->id, $snapshot->recommendations));

            $this->line('status=PASS');
            $this->line('runtime='.RuntimeMarker::current());
            $this->line('ruleset='.$context->rulesetId.'@'.$context->rulesetVersion);
            $this->line('ruleset_checksum='.$context->rulesetChecksumSha256);
            $this->line('parser='.$context->parserVersion);
            $this->line('findings='.implode(',', $findingCodes));
            $this->line('recommendations='.implode(',', $recommendationCodes));
            $this->line('recipe_count='.count($snapshot->recipes));
            $this->line('elapsed_ms='.$elapsed);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('status=BLOCKED');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
