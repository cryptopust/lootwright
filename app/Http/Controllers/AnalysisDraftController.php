<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AnalysisDraftController extends Controller
{
    /** @var list<string> */
    private const SAFE_FIELD_ALLOWLIST = [
        'character_class',
        'ascendancy',
        'alternate_ascendancy',
        'secondary_progression',
        'character_level',
        'league',
        'mode',
        'difficulty',
        'ruthless',
        'build_name',
        'main_skill',
        'secondary_skills',
        'archetype',
        'equipment_slot',
        'goals',
        'play_style',
        'priority',
        'problem',
        'description',
        'budget_amount',
        'budget_currency',
        'preserved_items',
        'replaceable_slots',
        'avoid',
        'must_keep',
        'notes',
        'storage_consent',
        'ai_explanation_opt_in',
    ];

    /** @var list<string> */
    private const SENSITIVE_FIELDS = ['pob', 'item_text', 'password'];

    public function show(Request $request): JsonResponse
    {
        $draft = DB::table('analysis_drafts')->where('user_id', $request->user()->id)->where('expires_at', '>', now())->latest()->first(['id', 'game_edition', 'flow', 'safe_fields', 'current_step', 'expires_at']);

        return response()->json(['draft' => $draft], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['game' => $request->input('game', 'poe1')]);
        $data = $request->validate([
            'flow' => ['required', Rule::in(['plan', 'analyse', 'upgrade'])], 'current_step' => ['required', 'integer', 'between:1,7'],
            'game' => ['required', Rule::in(config('game-editions.public', ['poe1']))],
            'safe_fields' => ['required', 'array', 'max:30'],
        ]);

        /** @var array<string, mixed> $submittedFields */
        $submittedFields = $request->input('safe_fields', []);
        $sensitiveFields = array_intersect(self::SENSITIVE_FIELDS, array_keys($submittedFields));
        if ($sensitiveFields !== []) {
            throw ValidationException::withMessages(array_fill_keys(
                array_map(static fn (string $field): string => "safe_fields.{$field}", $sensitiveFields),
                'Bu hassas alan taslakta saklanamaz.',
            ));
        }

        $safeFields = array_intersect_key($submittedFields, array_flip(self::SAFE_FIELD_ALLOWLIST));
        $existingId = DB::table('analysis_drafts')->where('user_id', $request->user()->id)->value('id');
        $id = is_string($existingId) ? $existingId : (string) Str::uuid7();
        $expiresAt = now()->addDays(7);
        DB::table('analysis_drafts')->updateOrInsert(
            ['user_id' => $request->user()->id],
            ['id' => $id, 'game_edition' => $data['game'], 'flow' => $data['flow'], 'safe_fields' => json_encode($safeFields, JSON_THROW_ON_ERROR), 'current_step' => $data['current_step'], 'expires_at' => $expiresAt, 'updated_at' => now(), ...($existingId === null ? ['created_at' => now()] : [])],
        );

        return response()->json(['draft_id' => $id, 'expires_at' => $expiresAt->toIso8601String()], 202, ['Cache-Control' => 'no-store']);
    }
}
