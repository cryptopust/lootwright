<?php

namespace App\Modules\ExternalSources;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\ExternalSources\DTO\SourceRegistryRecord;
use Lootwright\Application\ExternalSources\Ports\SourceRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;

final class DatabaseSourceRegistry implements SourceRegistry
{
    public function all(): array
    {
        $records = [];

        foreach (DB::table('policy_data_sources')->orderBy('id')->get() as $source) {
            $records[] = $this->record(get_object_vars($source));
        }

        return $records;
    }

    public function find(string $sourceCode): ?SourceRegistryRecord
    {
        $source = DB::table('policy_data_sources')->where('id', $sourceCode)->first();

        return $source === null ? null : $this->record(get_object_vars($source));
    }

    /** @param array<string, mixed> $source */
    private function record(array $source): SourceRegistryRecord
    {
        $sourceCode = $this->string($source, 'id');
        $versions = DB::table('policy_data_source_versions')->where('source_id', $sourceCode)->pluck('id');
        $rules = DB::table('policy_rules')->whereIn('source_version_id', $versions)->where('enabled', true)->get(['capability', 'operation', 'decision']);
        $allowed = $rules->filter(static fn (object $rule): bool => $rule->decision === 'allow')
            ->map(static fn (object $rule): string => $rule->capability.':'.$rule->operation)->unique()->sort()->values()->all();
        $forbidden = $rules->filter(static fn (object $rule): bool => $rule->decision !== 'allow')
            ->map(static fn (object $rule): string => $rule->capability.':'.$rule->operation)->unique()->sort()->values()->all();
        $evidence = array_values(DB::table('policy_permission_evidence')->whereIn('source_version_id', $versions)
            ->orderByDesc('retrieved_at')->get(['id', 'evidence_url', 'retrieved_at', 'effective_until', 'permission_status', 'summary'])
            ->map(static fn (object $row): array => get_object_vars($row))->all());
        $editionValues = json_decode($this->string($source, 'game_editions'), true, flags: JSON_THROW_ON_ERROR);
        $editions = [];

        foreach (is_array($editionValues) ? $editionValues : [] as $edition) {
            if (is_string($edition) && GameEdition::tryFrom($edition) instanceof GameEdition) {
                $editions[] = GameEdition::from($edition);
            }
        }

        $killSwitch = DB::table('policy_kill_switches')->where('active', true)
            ->where(static function ($query) use ($sourceCode): void {
                $query->where('scope', 'global')->orWhere(static function ($sourceQuery) use ($sourceCode): void {
                    $sourceQuery->where('scope', 'source')->where('source_id', $sourceCode);
                });
            })->exists();
        $governanceStatus = $this->string($source, 'governance_status');
        $configured = $this->configured($sourceCode);
        $enabled = $configured && ! $killSwitch && $governanceStatus !== 'prohibited';
        $reason = $enabled ? '' : match (true) {
            $killSwitch => 'emergency_kill_switch_active',
            $governanceStatus === 'prohibited' => 'source_prohibited',
            ! $configured => 'configuration_disabled_or_unapproved',
            default => 'policy_or_configuration_disabled',
        };

        return new SourceRegistryRecord(
            $sourceCode,
            $this->string($source, 'name'),
            $this->string($source, 'source_type'),
            $editions,
            $this->nullableString($source, 'reference_url'),
            $this->nullableString($source, 'documentation_url'),
            $this->nullableString($source, 'terms_url'),
            array_values($allowed),
            array_values($forbidden),
            $this->string($source, 'redistribution_status'),
            $this->string($source, 'commercial_use_status'),
            $this->string($source, 'cache_storage_status'),
            $this->nullableString($source, 'last_policy_review_at'),
            $evidence,
            $enabled,
            $killSwitch,
            $governanceStatus,
            $reason,
            $this->string($source, 'technical_access'),
            $this->string($source, 'license_identifier'),
            $this->string($source, 'rate_limit_status'),
            $this->string($source, 'auth_requirements'),
            $this->string($source, 'data_quality_status'),
            $this->string($source, 'patch_versioning_status'),
            $this->string($source, 'update_frequency'),
            $this->string($source, 'provenance_status'),
        );
    }

    /** @param array<string, mixed> $row */
    private function string(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (! is_string($value)) {
            throw new \RuntimeException("Expected string source registry field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function nullableString(array $row, string $key): ?string
    {
        return ($row[$key] ?? null) === null ? null : $this->string($row, $key);
    }

    private function configured(string $source): bool
    {
        return match ($source) {
            'GGG-POE1-SKILLTREE-001' => (bool) config('source-governance.ggg_passive_tree.enabled'),
            'POENINJA-ECONOMY-001' => (bool) config('external-sources.poe_ninja.enabled'),
            'POEWIKI-CARGO-001' => (bool) config('source-governance.poewiki_import_enabled'),
            'USER-POB-001', 'USER-ITEM-TEXT-001', 'LOOTWRIGHT-MANUAL-TRADE' => true,
            default => false,
        };
    }
}
