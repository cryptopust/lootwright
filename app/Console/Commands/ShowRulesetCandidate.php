<?php

namespace App\Console\Commands;

use App\Modules\Rulesets\PassiveTree\GggPassiveTreeImporter;
use Illuminate\Console\Command;
use Throwable;

/** Publishes a validated candidate; activation remains a separate command. */
final class ShowRulesetCandidate extends Command
{
    protected $signature = 'lootwright:ruleset:candidate
        {--file= : Absolute local path to an approved PoE1 passive-tree export}
        {--url= : Exact pinned approved export URL}';

    protected $description = 'Publish and report a validated PoE1 ruleset candidate without activation';

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
                ? $importer->importFile($file, false, false, true)
                : $importer->importUrl((string) $url, false, false, true);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Candidate status', $result->status);
        $this->components->twoColumnDetail('Ruleset activation', 'not performed');
        $this->components->twoColumnDetail('Source revision', $result->revision);
        $this->components->twoColumnDetail('Candidate checksum', $result->snapshotChecksumSha256 ?? 'unavailable');
        $this->components->twoColumnDetail('Ruleset ID', $result->rulesetVersionId ?? 'unavailable');

        return $result->status === 'succeeded' && $result->rulesetVersionId !== null
            ? self::SUCCESS
            : self::FAILURE;
    }
}
