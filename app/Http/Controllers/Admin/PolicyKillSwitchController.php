<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\KillSwitchScope;

class PolicyKillSwitchController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', Rule::enum(KillSwitchScope::class)],
            'source_id' => ['nullable', 'string', 'exists:policy_data_sources,id'],
            'capability' => ['nullable', Rule::enum(Capability::class)],
            'active' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $scope = KillSwitchScope::from($validated['scope']);
        $sourceId = $validated['source_id'] ?? null;
        $capability = $validated['capability'] ?? null;
        $validShape = match ($scope) {
            KillSwitchScope::Global => $sourceId === null && $capability === null,
            KillSwitchScope::Source => $sourceId !== null && $capability === null,
            KillSwitchScope::Capability => $sourceId === null && $capability !== null,
            KillSwitchScope::SourceCapability => $sourceId !== null && $capability !== null,
        };

        abort_unless($validShape, 422, 'Kill-switch scope does not match its source and capability fields.');

        DB::table('policy_kill_switches')->updateOrInsert(
            [
                'scope' => $scope->value,
                'source_id' => $sourceId,
                'capability' => $capability,
            ],
            [
                'active' => $validated['active'],
                'reason' => $validated['reason'],
                'activated_at' => $validated['active'] ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return response()->json(['status' => 'updated'], headers: ['Cache-Control' => 'no-store']);
    }
}
