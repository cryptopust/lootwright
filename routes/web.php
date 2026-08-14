<?php

use App\Http\Controllers\Admin\PolicyEvidenceController;
use App\Http\Controllers\Admin\PolicyKillSwitchController;
use App\Http\Controllers\CompareAnalysesController;
use App\Http\Controllers\DeletePobImportController;
use App\Http\Controllers\DeleteUserDataController;
use App\Http\Controllers\PobImportController;
use App\Http\Controllers\PolicyExplanationController;
use App\Http\Controllers\ReanalyzeController;
use App\Http\Controllers\RetrieveAnalysisController;
use App\Http\Controllers\SubmitAnalysisController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/policy/sources/{sourceId}', PolicyExplanationController::class)
    ->where('sourceId', '[A-Z][A-Z0-9-]{2,63}')
    ->middleware('throttle:30,1')
    ->name('policy.sources.show');

Route::post('/api/build-imports/pob', PobImportController::class)
    ->middleware('throttle:10,1')
    ->name('build-imports.pob.store');

Route::delete('/api/build-imports/pob/{id}', DeletePobImportController::class)
    ->whereUuid('id')
    ->middleware('throttle:20,1')
    ->name('build-imports.pob.delete');

Route::middleware(['auth', 'throttle:10,1'])->group(function (): void {
    Route::post('/api/analyses', SubmitAnalysisController::class)->name('analyses.submit');
    Route::get('/api/analyses/{analysisId}', RetrieveAnalysisController::class)->whereUuid('analysisId')->name('analyses.show');
    Route::get('/api/analyses/{leftId}/compare/{rightId}', CompareAnalysesController::class)
        ->whereUuid(['leftId', 'rightId'])
        ->name('analyses.compare');
    Route::post('/api/analyses/{analysisId}/reanalyze', ReanalyzeController::class)
        ->whereUuid('analysisId')
        ->name('analyses.reanalyze');
    Route::delete('/api/user-data', DeleteUserDataController::class)->name('user-data.delete');
});

Route::prefix('/admin/policy')
    ->middleware(['policy.admin', 'throttle:30,1'])
    ->group(function (): void {
        Route::get('/evidence', [PolicyEvidenceController::class, 'index']);
        Route::post('/evidence', [PolicyEvidenceController::class, 'store']);
        Route::post('/kill-switches', [PolicyKillSwitchController::class, 'store']);
    });
