<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReanalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'goals' => ['required_without:budget_amount', 'array', 'max:10'],
            'goals.*' => ['string', 'max:500', 'regex:/^[^\x00-\x08\x0B\x0C\x0E-\x1F]*$/D'],
            'budget_amount' => ['nullable', 'required_without:goals', 'string', 'max:20', 'regex:/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D'],
            'budget_currency' => ['nullable', 'required_with:budget_amount', 'string', 'max:12', 'regex:/^[A-Z][A-Z0-9_]{2,11}$/D'],
        ];
    }
}
