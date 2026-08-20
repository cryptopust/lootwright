<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Lootwright\Domain\PoeCatalog\Character\Poe1CharacterCatalog;
use Lootwright\Domain\PoeCatalog\Character\Poe2CharacterCatalog;

final class AdminPageController extends Controller
{
    public function audit(): Response
    {
        return Inertia::render('Admin/AuditLog', ['entries' => DB::table('admin_audit_logs')->join('users as actors', 'actors.id', '=', 'admin_audit_logs.actor_user_id')->latest('admin_audit_logs.created_at')->paginate(50, ['admin_audit_logs.id', 'admin_audit_logs.action', 'admin_audit_logs.reason', 'admin_audit_logs.metadata', 'admin_audit_logs.created_at', 'actors.email as actor_email'])]);
    }

    public function catalog(): Response
    {
        return Inertia::render('Admin/Catalog', ['catalogs' => [Poe1CharacterCatalog::current(), Poe2CharacterCatalog::current()]]);
    }

    public function system(): Response
    {
        return Inertia::render('Admin/System', ['failedJobs' => DB::table('failed_jobs')->count(), 'killSwitches' => DB::table('policy_kill_switches')->where('active', true)->get(['scope', 'source_id', 'capability', 'reason']), 'sourceRuns' => DB::table('external_source_sync_runs')->latest('started_at')->limit(20)->get(['source_key', 'status', 'league', 'category', 'started_at', 'completed_at', 'failure_code']), 'release' => config('deployment.release_sha')]);
    }
}
