<?php

namespace App\Modules\BuildIntake;

use Carbon\CarbonImmutable;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\PreparedPobInput;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class PolicyGatedPobImporter
{
    private const USER_SOURCE = 'USER-PASTED-POB';

    private const USER_VERSION = '1.0.0';

    public function __construct(
        private PobImportCoordinator $importer,
        private DecideCapability $policy,
        private PobImportStore $store,
        private LoggerInterface $logger,
    ) {}

    public function handle(
        string $input,
        bool $persist,
        ?int $retentionHours = null,
        ?string $idempotencyKey = null,
        ?string $actorId = null,
    ): PobImportExecution {
        if (! (bool) config('security.emergency.imports')) {
            throw new PobImportDisabled('Build imports are disabled by the emergency switch.');
        }

        $requestHash = hash('sha256', $input);

        try {
            $this->authorize(Capability::Import, 'user_input.pob_code.import', self::USER_SOURCE, self::USER_VERSION, ['explicit_user_submission']);
            $this->authorize(Capability::TransientProcess, 'user_input.pob_code.process', self::USER_SOURCE, self::USER_VERSION, ['explicit_user_submission']);

            $preparedResult = $this->importer->prepare($input);

            if ($preparedResult->isFailure()) {
                throw new PobImportRejected($preparedResult->error());
            }

            $prepared = $preparedResult->value();

            if (! $prepared instanceof PreparedPobInput) {
                throw new RuntimeException('The importer returned an invalid prepared input.');
            }

            $this->authorizeFormat($prepared->edition());
            $result = $this->importer->normalize($prepared);

            if ($result->isFailure()) {
                throw new PobImportRejected($result->error());
            }

            $import = $result->value();

            if (! $import instanceof PobImportResult) {
                throw new RuntimeException('The importer returned an invalid normalized result.');
            }

            $stored = null;

            if ($persist) {
                if (! PobImportIdempotency::isValid($idempotencyKey)) {
                    throw new PobImportRejected(DomainError::because(
                        DomainErrorCode::InvalidIdentifier,
                        'Persistent imports require a valid idempotency key.',
                    ));
                }

                $persistenceConditions = ['explicit_user_submission', 'user_storage_consent'];

                if (is_string($actorId) && trim($actorId) !== '') {
                    $persistenceConditions[] = 'authenticated_user';
                }

                $this->authorize(
                    Capability::PersistentStore,
                    'user_input.pob_code.store',
                    self::USER_SOURCE,
                    self::USER_VERSION,
                    $persistenceConditions,
                );

                if (! is_string($actorId) || trim($actorId) === '') {
                    throw new PobImportRejected(DomainError::because(
                        DomainErrorCode::InvalidIdentifier,
                        'Persistent imports require an authenticated owner.',
                    ));
                }

                $hours = $retentionHours ?? (int) config('build-intake.default_retention_hours', 24);
                $stored = $this->store->store($import, $requestHash, $hours, $idempotencyKey, $actorId);
            }

            $this->logger->info('pob_import_completed', [
                'request_hash_sha256' => $requestHash,
                'outcome' => 'normalized',
                'game_edition' => $import->canonicalBuild->edition->value,
                'parser_version' => $import->parserVersion,
                'persisted' => $stored !== null,
                'idempotent_replay' => $stored instanceof StoredPobImport ? $stored->replayed : false,
            ]);

            return new PobImportExecution($import, $stored);
        } catch (PobImportRejected $exception) {
            $this->logger->notice('pob_import_rejected', [
                'request_hash_sha256' => $requestHash,
                'outcome' => 'rejected',
                'error_code' => $exception->domainError->code->value,
            ]);

            throw $exception;
        } catch (PobPolicyDenied $exception) {
            $this->logger->notice('pob_import_policy_denied', [
                'request_hash_sha256' => $requestHash,
                'outcome' => 'policy_denied',
                'source_id' => $exception->decision->sourceId,
                'capability' => $exception->decision->capability->value,
                'reason' => $exception->decision->reason->value,
            ]);

            throw $exception;
        } catch (PobImportConflict $exception) {
            $this->logger->notice('pob_import_idempotency_conflict', [
                'request_hash_sha256' => $requestHash,
                'outcome' => 'idempotency_conflict',
            ]);

            throw $exception;
        }
    }

    private function authorizeFormat(GameEdition $edition): void
    {
        if ($edition === GameEdition::Poe1) {
            $this->authorize(
                Capability::DerivativeAnalysis,
                'pob.community.format_interpret',
                'POB-COMMUNITY',
                Pob1Normalizer::SOURCE_COMMIT,
                ['attribution_configured', 'independent_implementation', 'pinned_repository_version'],
            );

            return;
        }

        $this->authorize(
            Capability::DerivativeAnalysis,
            'pob2.community.format_interpret',
            'POB2-COMMUNITY',
            Pob2Normalizer::SOURCE_COMMIT,
            ['attribution_configured', 'independent_implementation', 'pinned_repository_version'],
        );
    }

    /** @param list<string> $conditions */
    private function authorize(
        Capability $capability,
        string $operation,
        string $sourceId,
        string $sourceVersion,
        array $conditions,
    ): void {
        $timestamp = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'))->value();

        if (! $timestamp instanceof RetrievedAt) {
            throw new RuntimeException('The policy timestamp is invalid.');
        }

        $request = CapabilityRequest::create(
            $capability,
            $operation,
            $sourceId,
            $sourceVersion,
            $timestamp,
            $conditions,
        )->value();

        if (! $request instanceof CapabilityRequest) {
            throw new RuntimeException('The capability request is invalid.');
        }

        $decision = $this->policy->handle($request)->value();

        if (! $decision instanceof CapabilityDecision || ! $decision->permitsExecution()) {
            if (! $decision instanceof CapabilityDecision) {
                throw new RuntimeException('The policy gate returned an invalid decision.');
            }

            throw new PobPolicyDenied($decision);
        }
    }
}
