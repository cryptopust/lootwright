<?php

namespace App\Http\Requests;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;

final class AiFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PrivacyPrincipalResolver::class)->resolve($this) !== null;
    }

    protected function failedAuthorization(): never
    {
        throw new AuthenticationException;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500', 'regex:/^[^\x00-\x08\x0B\x0C\x0E-\x1F]*$/D'],
            'ai_opt_in' => ['required', 'boolean'],
            'cache_permitted' => ['sometimes', 'boolean'],
        ];
    }
}
