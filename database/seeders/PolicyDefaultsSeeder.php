<?php

namespace Database\Seeders;

use App\Modules\PolicyProvenance\PolicyDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PolicyDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (PolicyDefaults::sources() as $source) {
                DB::table('policy_data_sources')->updateOrInsert(
                    ['id' => $source['id']],
                    [...$source, 'created_at' => now(), 'updated_at' => now()],
                );
            }

            foreach (PolicyDefaults::versions() as $version) {
                DB::table('policy_data_source_versions')->updateOrInsert(
                    ['source_id' => $version['source_id'], 'version' => $version['version']],
                    [...$version, 'created_at' => now(), 'updated_at' => now()],
                );
            }

            $versionIds = DB::table('policy_data_source_versions')
                ->get(['id', 'source_id', 'version'])
                ->keyBy(static fn (object $row): string => $row->source_id.':'.$row->version);

            foreach (PolicyDefaults::evidence() as $evidence) {
                $version = $versionIds->get($evidence['source_id'].':'.$evidence['source_version']);

                DB::table('policy_permission_evidence')->updateOrInsert(
                    ['id' => $evidence['id']],
                    [
                        'source_version_id' => $version->id,
                        'evidence_url' => $evidence['evidence_url'],
                        'retrieved_at' => $evidence['retrieved_at'],
                        'effective_from' => $evidence['effective_from'],
                        'effective_until' => $evidence['effective_until'],
                        'permission_status' => $evidence['permission_status'],
                        'attribution_required' => $evidence['attribution_required'],
                        'attribution_notice' => $evidence['attribution_notice'],
                        'summary' => $evidence['summary'],
                        'reviewer_role' => $evidence['reviewer_role'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            foreach (PolicyDefaults::rules() as $rule) {
                $version = $versionIds->get($rule['source_id'].':'.$rule['source_version']);

                DB::table('policy_rules')->updateOrInsert(
                    [
                        'source_version_id' => $version->id,
                        'capability' => $rule['capability'],
                        'operation' => $rule['operation'],
                    ],
                    [
                        'decision' => $rule['decision'],
                        'reason' => $rule['reason'],
                        'required_conditions' => json_encode($rule['required_conditions'], JSON_THROW_ON_ERROR),
                        'explanation' => $rule['explanation'],
                        'policy_version' => $rule['policy_version'],
                        'enabled' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            DB::table('policy_kill_switches')->updateOrInsert(
                ['scope' => 'global', 'source_id' => null, 'capability' => null],
                [
                    'active' => false,
                    'reason' => 'Emergency global disablement switch.',
                    'activated_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });
    }
}
