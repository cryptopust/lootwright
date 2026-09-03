<?php

namespace App\Http\Requests;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;

final class ReanalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PrivacyPrincipalResolver::class)->resolve($this) !== null;
    }

    protected function failedAuthorization(): never
    {
        throw new AuthenticationException;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'goals' => ['required_without:budget_amount', 'array', 'max:10'],
            'goals.*' => ['string', 'max:500', 'regex:/^[^\x00-\x08\x0B\x0C\x0E-\x1F]*$/D'],
            'budget_amount' => ['nullable', 'required_without:goals', 'string', 'max:20', 'regex:/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D'],
            'budget_currency' => ['nullable', 'required_with:budget_amount', 'string', 'max:12', 'regex:/^[A-Z][A-Z0-9_]{2,11}$/D'],
            'locked_items' => ['sometimes', 'array', 'max:50'],
            'locked_items.*' => ['string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D'],
            'donor_status' => ['prohibited'],
            'donor_badge' => ['prohibited'],
            'funding_tier' => ['prohibited'],
            'sponsor_rank' => ['prohibited'],
        ];
    }
}
