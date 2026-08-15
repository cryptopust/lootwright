<?php

namespace App\Modules\AI\OpenAi;

interface OpenAiHttpTransport
{
    /** @param array<string, mixed> $payload */
    public function postResponses(array $payload): OpenAiHttpResponse;
}
