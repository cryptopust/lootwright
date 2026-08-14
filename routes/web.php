<?php

use App\Http\Controllers\Admin\PolicyEvidenceController;
use App\Http\Controllers\Admin\PolicyKillSwitchController;
use App\Http\Controllers\PolicyExplanationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/policy/sources/{sourceId}', PolicyExplanationController::class)
    ->where('sourceId', '[A-Z][A-Z0-9-]{2,63}')
    ->middleware('throttle:30,1')
    ->name('policy.sources.show');

Route::prefix('/admin/policy')
    ->middleware(['policy.admin', 'throttle:30,1'])
    ->group(function (): void {
        Route::get('/evidence', [PolicyEvidenceController::class, 'index']);
        Route::post('/evidence', [PolicyEvidenceController::class, 'store']);
        Route::post('/kill-switches', [PolicyKillSwitchController::class, 'store']);
    });
