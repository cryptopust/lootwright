<?php

namespace App\Http\Controllers;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\ExportPortableAnalysis;

final class ExportAnalysisController extends Controller
{
    public function __invoke(string $analysisId, Request $request, ExportPortableAnalysis $useCase, PrivacyPrincipalResolver $principals): Response
    {
        $ownerId = $principals->resolve($request);

        if ($ownerId === null) {
            return response('Unauthorized', 401, ['Cache-Control' => 'no-store']);
        }

        try {
            $export = $useCase->handle($ownerId, $analysisId);
        } catch (WorkflowNotFound) {
            return response('Not Found', 404, ['Cache-Control' => 'no-store']);
        }

        return response($export->canonicalJson, 200, [
            'Cache-Control' => 'no-store, private',
            'Content-Disposition' => 'attachment; filename="lootwright-analysis-'.$analysisId.'.json"',
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Content-SHA256' => $export->sha256,
        ]);
    }
}
