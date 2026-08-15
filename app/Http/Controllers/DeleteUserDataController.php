<?php

namespace App\Http\Controllers;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Identity\Ports\PrivacySessionRepository;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\UseCases\DeleteUserData;

final class DeleteUserDataController extends Controller
{
    public function __invoke(
        Request $request,
        DeleteUserData $useCase,
        PrivacyPrincipalResolver $principals,
        PrivacySessionRepository $sessions,
    ): JsonResponse {
        $ownerId = $principals->resolve($request);

        if ($ownerId === null) {
            return response()->json(['status' => 'unauthorized'], 401, ['Cache-Control' => 'no-store']);
        }

        try {
            $result = $useCase->handle($ownerId);
        } catch (TransientWorkflowFailure) {
            return response()->json(['status' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        $credential = $request->header('X-Lootwright-Privacy-Session');
        if (is_string($credential)) {
            $sessions->markDeleted($credential);
        }

        return response()->json([
            'status' => 'deleted',
            'artifacts_deleted' => $result->artifactsDeleted,
            'analyses_deleted' => $result->analysesDeleted,
        ], headers: ['Cache-Control' => 'no-store']);
    }
}
