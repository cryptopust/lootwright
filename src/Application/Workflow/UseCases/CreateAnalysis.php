<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\AnalysisSelection;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;
use Lootwright\Application\Workflow\Ports\TransactionManager;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use RuntimeException;

final readonly class CreateAnalysis
{
    public function __construct(
        private WorkflowRepository $repository,
        private IdentifierGenerator $identifiers,
        private WorkflowDispatcher $dispatcher,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $ownerId, string $parentAnalysisId, AnalysisParameters $parameters): AnalysisRecord
    {
        $parent = $this->repository->analysisForOwner($parentAnalysisId, $ownerId);

        if ($parent === null) {
            throw new WorkflowNotFound('The analysis was not found.');
        }

        $parameters = $this->inheritSelection($parameters, $parent);
        $snapshot = $parameters->canonicalJson();
        $analysis = $this->transactions->run(function () use ($parent, $parameters, $snapshot): AnalysisRecord {
            $analysis = $this->repository->createAnalysisVersion(
                $this->identifiers->uuid7(),
                $parent,
                $snapshot,
                hash('sha256', $snapshot),
            );
            $this->dispatcher->analyze(
                $analysis->id,
                $analysis->edition,
                $parameters->selection?->rulesetChecksumSha256,
            );

            return $analysis;
        });

        if (! $analysis instanceof AnalysisRecord) {
            throw new RuntimeException('The analysis transaction returned an invalid result.');
        }

        return $analysis;
    }

    private function inheritSelection(AnalysisParameters $parameters, AnalysisRecord $parent): AnalysisParameters
    {
        if ($parameters->selection !== null) {
            return $parameters;
        }

        $parentParameters = json_decode($parent->parametersSnapshot, true);
        $selection = is_array($parentParameters) && is_array($parentParameters['selection'] ?? null)
            ? $parentParameters['selection']
            : null;

        if ($selection === null || ! is_string($selection['platform_realm'] ?? null)) {
            return $parameters;
        }

        return new AnalysisParameters(
            $parameters->goals,
            $parameters->budgetAmount,
            $parameters->budgetCurrency,
            new AnalysisSelection(
                PlatformRealm::from($selection['platform_realm']),
                is_string($selection['league'] ?? null) ? $selection['league'] : null,
                is_string($selection['content_goal'] ?? null) ? $selection['content_goal'] : null,
                is_string($selection['ruleset_id'] ?? null) ? $selection['ruleset_id'] : null,
                is_string($selection['ruleset_version'] ?? null) ? $selection['ruleset_version'] : null,
                is_string($selection['ruleset_checksum_sha256'] ?? null) ? $selection['ruleset_checksum_sha256'] : null,
                ($selection['ai_explanation_opt_in'] ?? false) === true,
            ),
            $parameters->lockedItems,
        );
    }
}
