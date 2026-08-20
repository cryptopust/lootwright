<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Domain\PoeCatalog\Character\CharacterCatalogRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;

final class CharacterOptionsController extends Controller
{
    public function __invoke(Request $request, string $game): JsonResponse
    {
        $edition = GameEdition::tryFrom($game);
        abort_if($edition === null, 404);

        $catalog = CharacterCatalogRegistry::for($edition);
        $payload = json_encode($catalog, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $etag = '"'.hash('sha256', $edition->value.':'.$payload).'"';
        $headers = ['ETag' => $etag, 'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400', 'Vary' => 'Accept'];
        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304, $headers);
        }

        return response()->json($catalog, headers: $headers);
    }
}
