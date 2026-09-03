<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Release\MvpReleaseDashboard;
use Inertia\Inertia;
use Inertia\Response;

final class AdminReleaseController extends Controller
{
    public function __invoke(MvpReleaseDashboard $dashboard): Response
    {
        return Inertia::render('Admin/Release', [
            'releaseGate' => $dashboard->report(),
        ]);
    }
}
