<?php

namespace App\Console\Commands;

use App\Modules\Rulesets\PassiveTree\GggPassiveTreeImporter;
use Illuminate\Console\Command;
use Throwable;

/** Safe, non-mutating validation entry point for governed dataset imports. */
final class ValidateDataset extends Command
{
    protected $signature = 'lootwright:dataset:validate
        {--file= : Absolute local path to an approved PoE1 passive-tree export}
        {--url= : Exact pinned approved export URL}';

    protected $description = 'Validate and normalize a governed dataset without storing or activating it';

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
                ? $importer->importFile($file, true, false)
                : $importer->importUrl((string) $url, true, false);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Status', $result->status);
        $this->components->twoColumnDetail('Edition', 'poe1');
        $this->components->twoColumnDetail('Records', $result->classCount.' classes / '.$result->nodeCount.' nodes');
        $this->components->twoColumnDetail('Source SHA-256', $result->sourceChecksumSha256);

        return $result->status === 'validated' ? self::SUCCESS : self::FAILURE;
    }
}
