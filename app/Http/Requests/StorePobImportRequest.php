<?php

namespace App\Http\Requests;

use App\Modules\BuildIntake\PobImportIdempotency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePobImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $maximumRetention = max(1, (int) config('build-intake.maximum_retention_hours', 168));

        return [
            'input' => ['nullable', 'string', 'required_without:build_file', 'prohibits:build_file'],
            'build_file' => ['nullable', 'file', 'required_without:input', 'prohibits:input', 'max:1024', 'mimetypes:text/plain'],
            'persist' => ['sometimes', 'boolean'],
            'storage_consent' => ['exclude_unless:persist,true', 'required', 'accepted'],
            'retention_hours' => ['exclude_unless:persist,true', 'sometimes', 'integer', 'min:1', 'max:'.$maximumRetention],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $input = $this->input('input');

            if (is_string($input) && strlen($input) > 1_048_576) {
                $validator->errors()->add('input', 'The build input may not exceed 1 MiB.');
            }

            if ($this->boolean('persist')) {
                $idempotencyKey = $this->header('Idempotency-Key');

                if (! PobImportIdempotency::isValid($idempotencyKey)) {
                    $validator->errors()->add('Idempotency-Key', 'Persistent imports require a high-entropy 32-128 character Idempotency-Key header.');
                }
            }
        }];
    }
}
