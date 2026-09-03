<?php

namespace App\Modules\BuildIntake;

use Carbon\CarbonImmutable;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\BuildImport\BuildImportCoordinator;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class PolicyGatedItemTextImporter
{
    public function __construct(
        private BuildImportCoordinator $importer,
        private DecideCapability $policy,
        private LoggerInterface $logger,
    ) {}

    public function handle(string $input, GameEdition $edition, ?ImportLimits $limits = null): PobImportResult
    {
        if (! (bool) config('security.emergency.imports')) {
            throw new PobImportDisabled('Build imports are disabled by the emergency switch.');
        }

        $requestHash = hash('sha256', $input);

        try {
            $this->authorize(Capability::Import, 'user_input.item_text.import');
            $this->authorize(Capability::TransientProcess, 'user_input.item_text.process');
            $result = $this->importer->import(
                $input,
                BuildInputType::ItemText,
                $edition,
                $limits ?? new ImportLimits,
            );

            if ($result->isFailure()) {
                throw new PobImportRejected($result->error());
            }

            $value = $result->value();

            if (! $value instanceof PobImportResult) {
                throw new RuntimeException('The item-text importer returned an invalid result.');
            }

            $this->logger->info('item_text_import_completed', [
                'request_hash_sha256' => $requestHash,
                'outcome' => 'normalized',
                'game_edition' => $edition->value,
                'parser_version' => $value->parserVersion,
            ]);

            return $value;
        } catch (PobImportRejected $exception) {
            $this->logger->notice('item_text_import_rejected', [
                'request_hash_sha256' => $requestHash,
                'outcome' => 'rejected',
                'error_code' => $exception->domainError->code->value,
            ]);

            throw $exception;
        }
    }

    private function authorize(Capability $capability, string $operation): void
    {
        $timestamp = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'))->value();

        if (! $timestamp instanceof RetrievedAt) {
            throw new RuntimeException('The policy timestamp is invalid.');
        }

        $request = CapabilityRequest::create(
            $capability,
            $operation,
            'USER-ITEM-TEXT-001',
            '1.0.0',
            $timestamp,
            ['explicit_user_submission'],
        )->value();

        if (! $request instanceof CapabilityRequest) {
            throw new RuntimeException('The item-text capability request is invalid.');
        }

        $decision = $this->policy->handle($request)->value();

        if (! $decision instanceof CapabilityDecision || ! $decision->permitsExecution()) {
            if (! $decision instanceof CapabilityDecision) {
                throw new RuntimeException('The Policy Gate returned an invalid decision.');
            }

            throw new PobPolicyDenied($decision);
        }
    }
}
