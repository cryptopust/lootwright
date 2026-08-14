<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\UseCases\DeleteUserData;

final class DeleteUserDataController extends Controller
{
    public function __invoke(Request $request, DeleteUserData $useCase): JsonResponse
    {
        $owner = $request->user()?->getAuthIdentifier();
        $ownerId = is_int($owner) || is_string($owner) ? (string) $owner : '';

        try {
            $result = $useCase->handle($ownerId);
        } catch (TransientWorkflowFailure) {
            return response()->json(['status' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        return response()->json([
            'status' => 'deleted',
            'artifacts_deleted' => $result->artifactsDeleted,
            'analyses_deleted' => $result->analysesDeleted,
        ], headers: ['Cache-Control' => 'no-store']);
    }
}
