<?php

namespace App\Modules\BuildIntake;

use App\Modules\PolicyProvenance\LocalFixtureCapabilityPolicy;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Psr\Log\NullLogger;

/**
 * Database-free, deny-by-default adapter for original local evaluation fixtures.
 */
final readonly class LocalEvaluationPobImporter
{
    public function __construct(private PobImportCoordinator $importer) {}

    public function import(string $input, ?ImportLimits $limits = null): DomainResult
    {
        $gated = new PolicyGatedPobImporter(
            $this->importer,
            new DecideCapability(new LocalFixtureCapabilityPolicy((bool) config('policy.global_kill_switch'))),
            new PobImportStore,
            new NullLogger,
        );

        try {
            return DomainResult::success($gated->handle(
                $input,
                false,
                limits: $limits,
                allowInactiveEditionForEvaluation: true,
            )->result);
        } catch (PobImportRejected $exception) {
            return DomainResult::failure($exception->domainError);
        }
    }
}
