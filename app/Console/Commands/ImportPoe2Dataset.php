<?php

namespace App\Console\Commands;

use App\Modules\ExternalSources\Poe2\Poe2DatasetImporter;
use Illuminate\Console\Command;
use Throwable;

final class ImportPoe2Dataset extends Command
{
    protected $signature = 'lootwright:poe2:dataset
        {--file= : Absolute local path to the approved PoE2 dataset}
        {--dry-run : Validate and checksum without storing}
        {--activate : Publish and activate the PoE2 ruleset}
        {--force : Confirm the production-affecting activation}';

    protected $description = 'Validate or import the independent PoE2 canonical dataset';

    public function handle(Poe2DatasetImporter $importer): int
    {
        $file = $this->option('file');
        if (! is_string($file) || $file === '') {
            $this->error('Provide --file with an approved local PoE2 dataset.');

            return self::FAILURE;
        }
        if ($this->option('activate') && ! $this->option('force')) {
            $this->error('Activation is production-affecting; repeat with --activate --force.');

            return self::FAILURE;
        }
        try {
            $result = $this->option('dry-run')
                ? $importer->validateFile($file)
                : $importer->importFile($file, (bool) $this->option('activate'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->components->twoColumnDetail('Status', $result->status);
        $this->components->twoColumnDetail('Edition', 'poe2');
        $this->components->twoColumnDetail('Game version', '0.3.0');
        $this->components->twoColumnDetail('Records', (string) $result->recordCount);
        $this->components->twoColumnDetail('Source SHA-256', $result->sourceChecksumSha256);
        $this->components->twoColumnDetail('Normalized SHA-256', $result->normalizedChecksumSha256);
        if ($result->snapshotId !== null) $this->components->twoColumnDetail('Snapshot ID', $result->snapshotId);
        if ($result->rulesetId !== null) $this->components->twoColumnDetail('Ruleset ID', $result->rulesetId);

        return in_array($result->status, ['validated', 'succeeded'], true) ? self::SUCCESS : self::FAILURE;
    }
}
