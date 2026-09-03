<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** Canonical dataset import name; delegates to the reviewed PoE1 importer. */
final class ImportDataset extends Command
{
    protected $signature = 'lootwright:dataset:import
        {--file= : Absolute local path to an approved PoE1 passive-tree export}
        {--url= : Exact pinned approved export URL}
        {--dry-run : Validate without storing}
        {--activate : Publish and atomically activate the candidate}
        {--force : Confirm this production-affecting operation}';

    protected $description = 'Import a governed dataset through its edition-specific adapter';

    public function handle(): int
    {
        $arguments = [];
        if (is_string($file = $this->option('file')) && $file !== '') {
            $arguments['--file'] = $file;
        }
        if (is_string($url = $this->option('url')) && $url !== '') {
            $arguments['--url'] = $url;
        }
        if ($this->option('dry-run')) {
            $arguments['--dry-run'] = true;
        }
        if ($this->option('activate')) {
            if (! $this->option('force')) {
                $this->error('Activation is production-affecting; repeat with --activate --force.');

                return self::FAILURE;
            }
            $arguments['--activate'] = true;
        }

        return $this->call('poe:import-passive-tree', $arguments);
    }
}
