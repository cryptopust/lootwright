<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lootwright\Application\Identity\UseCases\CreateAnonymousPrivacySession;

final class CreatePrivacySessionController extends Controller
{
    public function __invoke(CreateAnonymousPrivacySession $useCase): JsonResponse
    {
        $credential = $useCase->handle();

        return response()->json([
            'status' => 'created',
            'session' => [
                'id' => $credential->id,
                'token' => $credential->token,
                'expires_at' => $credential->expiresAt,
            ],
        ], 201, ['Cache-Control' => 'no-store, private']);
    }
}
