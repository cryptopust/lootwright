<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Domain\PoeCatalog\Character\Poe1CharacterCatalog;

final class Poe1CharacterOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $catalog = Poe1CharacterCatalog::current();
        $payload = json_encode($catalog, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $etag = '"'.hash('sha256', $payload).'"';
        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304, ['ETag' => $etag, 'Cache-Control' => 'public, max-age=3600']);
        }

        return response()->json($catalog, headers: ['ETag' => $etag, 'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400']);
    }
}
