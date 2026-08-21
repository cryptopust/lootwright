<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\AdminAuditLogger;
use App\Modules\ExternalSources\Jobs\RunExternalSourceImportJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;

final class AdminSourceImportController extends Controller
{
    public function __invoke(
        Request $request,
        ExternalSourceAdapterCatalog $catalog,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        /** @var array{source_code: string, reason: string} $validated */
        $validated = $request->validate([
            'source_code' => ['required', 'string', 'regex:/^[A-Z][A-Z0-9-]{2,63}$/'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $adapter = $catalog->find($validated['source_code']);
        abort_if($adapter === null, 404);
        abort_unless($adapter->status()->operational, 422, 'This source adapter is disabled by policy or configuration.');

        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->isSuperAdmin(), 403);
        abort_if(config('queue.default') === 'sync', 503, 'A non-synchronous source-import queue is required.');

        RunExternalSourceImportJob::dispatch($validated['source_code']);
        $audit->record(
            $actor,
            'external_source.import_requested',
            $validated['reason'],
            metadata: ['source_code' => $validated['source_code'], 'dispatch_mode' => 'queued'],
        );

        return back()->with('status', 'Kaynak içe aktarma işi kuyruğa alındı.');
    }
}
