<?php

namespace App\Http\Controllers;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\DeleteBuild;

final class DeleteBuildController extends Controller
{
    public function __invoke(string $buildId, Request $request, DeleteBuild $useCase, PrivacyPrincipalResolver $principals): JsonResponse
    {
        $ownerId = $principals->resolve($request);

        if ($ownerId === null) {
            return response()->json(['status' => 'unauthorized'], 401, ['Cache-Control' => 'no-store']);
        }

        try {
            $result = $useCase->handle($ownerId, $buildId);
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        } catch (TransientWorkflowFailure) {
            return response()->json(['status' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        return response()->json([
            'status' => 'deleted',
            'build_id' => $result->buildId,
            'analyses_deleted' => $result->analysesDeleted,
        ], headers: ['Cache-Control' => 'no-store, private']);
    }
}
