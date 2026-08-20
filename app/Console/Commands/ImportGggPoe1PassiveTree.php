<?php

namespace App\Console\Commands;

use App\Modules\Rulesets\PassiveTree\GggPassiveTreeImporter;
use Illuminate\Console\Command;
use Throwable;

final class ImportGggPoe1PassiveTree extends Command
{
    protected $signature = 'poe:import-passive-tree
        {--file= : Absolute path to an approved official data.json export}
        {--url= : Exact commit-pinned raw.githubusercontent.com GGG data.json URL}
        {--dry-run : Validate and normalize without storing a snapshot}
        {--activate : Publish and atomically activate a ruleset from the imported snapshot}';

    protected $description = 'Import a governed, commit-pinned official GGG PoE1 passive-tree export';

    public function handle(GggPassiveTreeImporter $importer): int
    {
        $file = $this->option('file');
        $url = $this->option('url');
        if ((is_string($file) && $file !== '') === (is_string($url) && $url !== '')) {
            $this->error('Provide exactly one of --file or --url.');

            return self::FAILURE;
        }

        try {
            $result = is_string($file) && $file !== ''
                ? $importer->importFile($file, (bool) $this->option('dry-run'), (bool) $this->option('activate'))
                : $importer->importUrl((string) $url, (bool) $this->option('dry-run'), (bool) $this->option('activate'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Status', $result->status);
        $this->components->twoColumnDetail('Upstream commit', $result->revision);
        $this->components->twoColumnDetail('Source SHA-256', $result->sourceChecksumSha256);
        if ($result->snapshotChecksumSha256 !== null) {
            $this->components->twoColumnDetail('Snapshot SHA-256', $result->snapshotChecksumSha256);
        }
        if ($result->snapshotId !== null) {
            $this->components->twoColumnDetail('Snapshot ID', $result->snapshotId);
        }
        if ($result->rulesetVersionId !== null) {
            $this->components->twoColumnDetail('Ruleset ID', $result->rulesetVersionId);
        }
        $this->components->twoColumnDetail('Classes / nodes', $result->classCount.' / '.$result->nodeCount);
        $this->components->twoColumnDetail('Duplicate snapshot', $result->replayed ? 'yes' : 'no');
        if ($result->failureCode !== null) {
            $this->components->twoColumnDetail('Failure code', $result->failureCode);
        }

        return in_array($result->status, ['validated', 'succeeded'], true) ? self::SUCCESS : self::FAILURE;
    }
}
