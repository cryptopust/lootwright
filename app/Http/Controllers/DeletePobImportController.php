<?php

namespace App\Http\Controllers;

use App\Modules\BuildIntake\PobImportStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletePobImportController extends Controller
{
    public function __invoke(string $id, Request $request, PobImportStore $store): JsonResponse
    {
        $validated = $request->validate([
            'deletion_token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/D'],
        ]);

        $deleted = $store->delete($id, $validated['deletion_token']);

        if (! $deleted) {
            return response()->json([
                'status' => 'not_found',
            ], 404);
        }

        return response()->json([
            'status' => 'deleted',
        ]);
    }
}
