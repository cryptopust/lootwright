<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Lootwright\Domain\PoeCatalog\Character\CharacterCatalogRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;

final class SubmitWizardAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user->hasVerifiedEmail() && $user->isActive();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'between:32,128', 'regex:/^[A-Za-z0-9._:-]+$/D'],
            'flow' => ['required', Rule::in(['plan', 'analyse', 'upgrade'])],
            'game' => ['required', Rule::enum(GameEdition::class)],
            'character_class' => ['required', 'string', 'max:128'],
            'ascendancy' => ['nullable', 'string', 'max:128'],
            'alternate_ascendancy' => ['nullable', 'string', 'max:128'],
            'secondary_progression' => ['nullable', 'string', 'max:128'],
            'character_level' => ['required', 'integer', 'between:1,100'],
            'league' => ['nullable', Rule::in(['standard'])],
            'mode' => ['required', Rule::in(['trade', 'ssf'])],
            'difficulty' => ['required', Rule::in(['softcore', 'hardcore'])],
            'build_name' => ['nullable', 'string', 'max:120'],
            'main_skill' => ['nullable', 'string', 'max:120'],
            'secondary_skills' => ['array', 'max:8'], 'secondary_skills.*' => ['string', 'max:120'],
            'archetype' => ['nullable', 'string', 'max:120'],
            'pob' => ['nullable', 'string', 'max:1048576'],
            'item_text' => ['nullable', 'string', 'max:65536'],
            'equipment_slot' => ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9._-]+$/D'],
            'goals' => ['required', 'array', 'between:1,10'], 'goals.*' => ['string', Rule::in(['mapping', 'bossing', 'league_starter', 'all_rounder', 'delve', 'expedition', 'heist', 'sanctum', 'simulacrum', 'defence', 'speed'])],
            'play_style' => ['required', 'string', 'max:128'],
            'priority' => ['required', Rule::in(['damage', 'defence', 'clear_speed', 'boss_damage', 'budget_efficiency'])],
            'problem' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string', 'max:1000'],
            'budget_amount' => ['nullable', 'string', 'regex:/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D'],
            'budget_currency' => ['nullable', 'required_with:budget_amount', Rule::in(['CHAOS', 'DIVINE'])],
            'preserved_items' => ['array', 'max:20'], 'replaceable_slots' => ['array', 'max:20'], 'avoid' => ['nullable', 'string', 'max:1000'], 'must_keep' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:1000'],
            'storage_consent' => ['required', 'accepted'], 'ai_explanation_opt_in' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $edition = GameEdition::tryFrom($this->string('game')->toString());
            if ($edition === null || ! CharacterCatalogRegistry::for($edition)->supports(
                $this->string('character_class')->toString(),
                $this->filled('ascendancy') ? $this->string('ascendancy')->toString() : null,
                $this->filled('alternate_ascendancy') ? $this->string('alternate_ascendancy')->toString() : null,
                $this->filled('secondary_progression') ? $this->string('secondary_progression')->toString() : null,
            )) {
                $validator->errors()->add('ascendancy', 'Seçilen sınıf ve progression bu oyunun oynanabilir kataloğuyla uyuşmuyor.');
            }
            if ($this->input('flow') === 'analyse' && ! $this->filled('pob')) {
                $validator->errors()->add('pob', 'Var olan build analizi için PoB kodu gereklidir.');
            }
            if ($this->input('flow') === 'upgrade' && ! $this->filled('item_text') && ! $this->filled('equipment_slot')) {
                $validator->errors()->add('item_text', 'Yükseltme akışı için item metni veya ekipman slotu gereklidir.');
            }
        }];
    }
}
