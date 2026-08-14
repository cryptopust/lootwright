<?php

namespace App\Modules\PolicyProvenance;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Domain\PolicyProvenance\AccessMode;
use Lootwright\Domain\PolicyProvenance\AttributionRequirement;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\DataSource;
use Lootwright\Domain\PolicyProvenance\DataSourceVersion;
use Lootwright\Domain\PolicyProvenance\EffectivePeriod;
use Lootwright\Domain\PolicyProvenance\EvidenceUrl;
use Lootwright\Domain\PolicyProvenance\KillSwitch;
use Lootwright\Domain\PolicyProvenance\KillSwitchScope;
use Lootwright\Domain\PolicyProvenance\PermissionEvidence;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\PolicyEvaluator;
use Lootwright\Domain\PolicyProvenance\PolicyRule;
use Lootwright\Domain\PolicyProvenance\PolicyVersion;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\PolicyProvenance\SourceType;
use Lootwright\Domain\Shared\Error\DomainResult;
use RuntimeException;
use Throwable;

final readonly class DatabaseCapabilityPolicy implements CapabilityPolicy
{
    public function __construct(private PolicyEvaluator $evaluator) {}

    public function authorize(CapabilityRequest $request): DomainResult
    {
        try {
            $versionRow = DB::table('policy_data_source_versions as versions')
                ->join('policy_data_sources as sources', 'sources.id', '=', 'versions.source_id')
                ->where('versions.source_id', $request->sourceId)
                ->where('versions.version', $request->sourceVersion)
                ->first([
                    'versions.id',
                    'versions.source_id',
                    'versions.version',
                    'versions.policy_version',
                    'sources.name',
                    'sources.source_type',
                    'sources.access_mode',
                ]);

            $rules = [];
            $evidence = [];

            if ($versionRow !== null) {
                $versionData = get_object_vars($versionRow);
                $versionId = $this->integer($versionData, 'id');
                $sourceVersion = $this->sourceVersion($versionData);
                $ruleRow = DB::table('policy_rules')
                    ->where('source_version_id', $versionId)
                    ->where('capability', $request->capability->value)
                    ->where('operation', $request->operation)
                    ->where('enabled', true)
                    ->first();

                if ($ruleRow !== null) {
                    $rules[] = $this->rule($sourceVersion, get_object_vars($ruleRow));
                }

                foreach (DB::table('policy_permission_evidence')
                    ->where('source_version_id', $versionId)
                    ->get() as $evidenceRow) {
                    $evidence[] = $this->evidence($sourceVersion, get_object_vars($evidenceRow));
                }
            }

            $killSwitches = $this->killSwitches();
            $decision = $this->evaluator->decide($request, $rules, $evidence, $killSwitches);
            $this->audit($request, $decision);

            return DomainResult::success($decision);
        } catch (Throwable) {
            return DomainResult::success(new CapabilityDecision(
                $request->capability,
                $request->sourceId,
                PolicyDecision::Deny,
                PolicyDecisionReason::MissingRule,
                PolicyVersion::baseline(),
                'The policy service is unavailable; execution is denied.',
            ));
        }
    }

    /** @param array<string, mixed> $row */
    private function sourceVersion(array $row): DataSourceVersion
    {
        $source = $this->value(DataSource::create(
            $this->string($row, 'source_id'),
            $this->string($row, 'name'),
            SourceType::from($this->string($row, 'source_type')),
            AccessMode::from($this->string($row, 'access_mode')),
        ), DataSource::class);
        $policyVersion = $this->value(
            PolicyVersion::from($this->string($row, 'policy_version')),
            PolicyVersion::class,
        );

        return $this->value(
            DataSourceVersion::create($source, $this->string($row, 'version'), $policyVersion),
            DataSourceVersion::class,
        );
    }

    /** @param array<string, mixed> $row */
    private function rule(DataSourceVersion $version, array $row): PolicyRule
    {
        $conditions = $this->stringList(json_decode(
            $this->string($row, 'required_conditions'),
            true,
            flags: JSON_THROW_ON_ERROR,
        ));

        return $this->value(PolicyRule::create(
            $version,
            Capability::from($this->string($row, 'capability')),
            $this->string($row, 'operation'),
            PolicyDecision::from($this->string($row, 'decision')),
            PolicyDecisionReason::from($this->string($row, 'reason')),
            $conditions,
            $this->string($row, 'explanation'),
        ), PolicyRule::class);
    }

    /** @param array<string, mixed> $row */
    private function evidence(DataSourceVersion $version, array $row): PermissionEvidence
    {
        $url = $this->value(
            EvidenceUrl::from($this->string($row, 'evidence_url')),
            EvidenceUrl::class,
        );
        $retrievedAt = $this->timestamp($this->string($row, 'retrieved_at'));
        $effectiveFrom = $this->timestamp($this->string($row, 'effective_from'));
        $effectiveUntilValue = $this->nullableString($row, 'effective_until');
        $effectiveUntil = $effectiveUntilValue === null
            ? null
            : $this->timestamp($effectiveUntilValue);
        $period = $this->value(
            EffectivePeriod::create($effectiveFrom, $effectiveUntil),
            EffectivePeriod::class,
        );
        $attribution = $this->boolean($row, 'attribution_required')
            ? $this->value(
                AttributionRequirement::required(
                    $this->string($row, 'attribution_notice'),
                ),
                AttributionRequirement::class,
            )
            : AttributionRequirement::none();

        return $this->value(PermissionEvidence::create(
            $this->string($row, 'id'),
            $version,
            $url,
            $retrievedAt,
            $period,
            PermissionStatus::from($this->string($row, 'permission_status')),
            $attribution,
            $this->string($row, 'summary'),
        ), PermissionEvidence::class);
    }

    /** @return list<KillSwitch> */
    private function killSwitches(): array
    {
        $switches = [];

        if (config('policy.global_kill_switch')) {
            $switches[] = new KillSwitch(KillSwitchScope::Global, true);
        }

        foreach (DB::table('policy_kill_switches')->where('active', true)->get() as $row) {
            $data = get_object_vars($row);
            $capability = $this->nullableString($data, 'capability');
            $switches[] = new KillSwitch(
                KillSwitchScope::from($this->string($data, 'scope')),
                true,
                $this->nullableString($data, 'source_id'),
                $capability === null ? null : Capability::from($capability),
            );
        }

        return $switches;
    }

    private function audit(CapabilityRequest $request, CapabilityDecision $decision): void
    {
        DB::table('policy_decision_audits')->insert([
            'id' => (string) Str::uuid7(),
            'source_id' => $request->sourceId,
            'source_version' => $request->sourceVersion,
            'capability' => $request->capability->value,
            'operation' => $request->operation,
            'decision' => $decision->decision->value,
            'reason' => $decision->reason->value,
            'policy_version' => $decision->policyVersion->value,
            'evidence_ids' => json_encode($decision->evidenceIds, JSON_THROW_ON_ERROR),
            'condition_flags' => json_encode($request->satisfiedConditions, JSON_THROW_ON_ERROR),
            'evaluated_at' => $request->evaluatedAt->value,
            'actor_type' => 'application',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function timestamp(string $value): RetrievedAt
    {
        $canonical = CarbonImmutable::parse($value)->utc()->format('Y-m-d\TH:i:s\Z');

        return $this->value(RetrievedAt::from($canonical), RetrievedAt::class);
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException("Expected string database field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        if (($data[$key] ?? null) === null) {
            return null;
        }

        return $this->string($data, $key);
    }

    /** @param array<string, mixed> $data */
    private function integer(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (! is_int($value)) {
            throw new RuntimeException("Expected integer database field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function boolean(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;

        if (! is_bool($value) && ! is_int($value)) {
            throw new RuntimeException("Expected boolean database field {$key}.");
        }

        return (bool) $value;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new RuntimeException('Expected a list of policy condition names.');
        }

        $strings = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new RuntimeException('Expected a string policy condition name.');
            }

            $strings[] = $item;
        }

        return $strings;
    }

    /**
     * @template TObject of object
     *
     * @param  class-string<TObject>  $expectedClass
     * @return TObject
     */
    private function value(DomainResult $result, string $expectedClass): object
    {
        $value = $result->value();

        if (! $value instanceof $expectedClass) {
            throw new RuntimeException("Expected {$expectedClass} from domain result.");
        }

        return $value;
    }
}
