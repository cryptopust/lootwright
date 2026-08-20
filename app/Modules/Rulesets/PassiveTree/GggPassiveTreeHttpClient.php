<?php

namespace App\Modules\Rulesets\PassiveTree;

use App\Security\OutboundRequestGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class GggPassiveTreeHttpClient
{
    public function __construct(private OutboundRequestGuard $outbound) {}

    public function fetch(string $url): string
    {
        GggPassiveTreeUrl::revision($url);
        $contact = trim((string) config('source-governance.ggg_passive_tree.contact'));
        if ($contact === '') {
            throw new RuntimeException('GGG_PASSIVE_TREE_CONTACT is required for URL imports.');
        }

        $this->outbound->assertAllowed('ggg.poe1.skilltree.export.fetch', $url);
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Lootwright/'.config('source-governance.ggg_passive_tree.user_agent_version').' (contact: '.$contact.')',
                'Accept' => 'application/json',
            ])->connectTimeout((int) config('source-governance.ggg_passive_tree.connect_timeout_seconds'))
                ->timeout((int) config('source-governance.ggg_passive_tree.request_timeout_seconds'))
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException('The official export request failed or timed out.');
        }

        if ($response->redirect()) {
            throw new RuntimeException('Redirects are denied for passive-tree imports.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('The official export returned HTTP '.$response->status().'.');
        }
        $contentLength = $response->header('Content-Length');
        if (ctype_digit($contentLength) && (int) $contentLength > GggPassiveTreeImporter::MAX_SOURCE_BYTES) {
            throw new RuntimeException('The official export exceeds the configured size limit.');
        }
        $body = $response->body();
        if (strlen($body) > GggPassiveTreeImporter::MAX_SOURCE_BYTES) {
            throw new RuntimeException('The official export exceeds the configured size limit.');
        }

        return $body;
    }
}
