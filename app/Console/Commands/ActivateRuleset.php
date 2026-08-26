<?php

namespace App\Console\Commands;

use App\Modules\Rulesets\PostgresGovernedRulesetRepository;
use Illuminate\Console\Command;
use Throwable;

final class ActivateRuleset extends Command
{
    protected $signature = 'lootwright:ruleset:activate
        {ruleset : UUID of a published, approved ruleset}
        {--actor=operator : Canonical audit actor type}
        {--force : Confirm this production-affecting operation}';

    protected $description = 'Atomically activate a validated approved ruleset';

    public function handle(PostgresGovernedRulesetRepository $repository): int
    {
        if (! $this->option('force')) {
            $this->error('Activation is production-affecting; repeat with --force.');

            return self::FAILURE;
        }

        try {
            $activation = $repository->activate((string) $this->argument('ruleset'), (string) $this->option('actor'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Status', 'activated');
        $this->components->twoColumnDetail('Edition', $activation->edition->value);
        $this->components->twoColumnDetail('Ruleset', $activation->rulesetVersionId);

        return self::SUCCESS;
    }
}
