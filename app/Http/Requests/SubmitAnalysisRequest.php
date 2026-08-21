<?php

namespace App\Http\Requests;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\PlatformRealm;

final class SubmitAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PrivacyPrincipalResolver::class)->resolve($this) !== null;
    }

    protected function failedAuthorization(): never
    {
        throw new AuthenticationException;
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
            'game' => ['required', Rule::in(config('game-editions.public', ['poe1']))],
            'platform_realm' => ['sometimes', Rule::enum(PlatformRealm::class)],
            'locale' => ['required', 'string', 'max:32', 'regex:/^[a-z]{2,3}(?:-[A-Z][a-z]{3})?(?:-(?:[A-Z]{2}|\d{3}))?$/D'],
            'artifact_type' => ['required', Rule::in(['pob'])],
            'artifact' => ['required', 'string'],
            'storage_consent' => ['required', 'accepted'],
            'goals' => ['sometimes', 'array', 'max:10'],
            'goals.*' => ['string', 'max:500', 'regex:/^[^\x00-\x08\x0B\x0C\x0E-\x1F]*$/D'],
            'budget_amount' => ['nullable', 'string', 'max:20', 'regex:/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D'],
            'budget_currency' => ['nullable', 'required_with:budget_amount', 'string', 'max:12', 'regex:/^[A-Z][A-Z0-9_]{2,11}$/D'],
            'league' => ['nullable', 'string', 'max:128', 'regex:/^[a-z][a-z0-9._-]{1,127}$/D'],
            'content_goal' => ['nullable', 'string', 'max:128', 'regex:/^[a-z][a-z0-9._-]{1,127}$/D'],
            'ruleset_id' => ['nullable', 'required_with:ruleset_version,ruleset_checksum_sha256', 'uuid'],
            'ruleset_version' => [
                'nullable',
                'required_with:ruleset_id,ruleset_checksum_sha256',
                'string',
                'max:64',
                'regex:/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9a-z.-]+)?$/D',
            ],
            'ruleset_checksum_sha256' => ['nullable', 'required_with:ruleset_id,ruleset_version', 'string', 'size:64', 'regex:/^[0-9a-f]+$/D'],
            'ai_explanation_opt_in' => ['sometimes', 'boolean'],
            'donor_status' => ['prohibited'],
            'donor_badge' => ['prohibited'],
            'funding_tier' => ['prohibited'],
            'sponsor_rank' => ['prohibited'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $artifact = $this->input('artifact');

            if (is_string($artifact) && strlen($artifact) > 1_048_576) {
                $validator->errors()->add('artifact', 'The build artifact may not exceed 1 MiB.');
            }

            $contentType = $this->header('Content-Type');

            if (! is_string($contentType) || ! str_starts_with(strtolower($contentType), 'application/json')) {
                $validator->errors()->add('Content-Type', 'The analysis endpoint accepts application/json requests only.');
            }

            $edition = GameEdition::tryFrom((string) $this->input('game'));
            $realm = PlatformRealm::tryFrom((string) ($this->input('platform_realm') ?: ($edition === GameEdition::Poe2 ? 'poe2' : 'pc')));

            if ($edition !== null && ($realm === null || ! $realm->supports($edition))) {
                $validator->errors()->add('platform_realm', 'The platform realm does not support the selected edition.');
            }
        }];
    }
}
