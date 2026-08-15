<?php

use App\Http\Controllers\Admin\PolicyEvidenceController;
use App\Http\Controllers\Admin\PolicyKillSwitchController;
use App\Http\Controllers\AnalysisProvenanceController;
use App\Http\Controllers\CompareAnalysesController;
use App\Http\Controllers\CreatePrivacySessionController;
use App\Http\Controllers\DeleteBuildController;
use App\Http\Controllers\DeletePobImportController;
use App\Http\Controllers\DeleteUserDataController;
use App\Http\Controllers\ExportAnalysisController;
use App\Http\Controllers\FundingController;
use App\Http\Controllers\PobImportController;
use App\Http\Controllers\PolicyExplanationController;
use App\Http\Controllers\ReanalyzeController;
use App\Http\Controllers\RetrieveAnalysisController;
use App\Http\Controllers\SubmitAnalysisController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Landing')->name('home');
Route::inertia('/analyses/new', 'Analysis/New')->name('analyses.new');
Route::inertia('/analyses/demo/import', 'Analysis/ImportReview')->name('analyses.demo.import');
Route::get('/analyses/demo/{section}', static fn (string $section) => Inertia::render('Analysis/Workspace', [
    'section' => $section,
    'externalLinksEnabled' => (bool) config('security.emergency.external_links'),
]))
    ->where('section', 'overview|findings|upgrades|trade|provenance|states')
    ->name('analyses.demo.workspace');

Route::inertia('/privacy', 'Information', ['page' => 'privacy'])->name('privacy');
Route::inertia('/data-deletion', 'Information', ['page' => 'deletion'])->name('data-deletion');
Route::inertia('/methodology', 'Information', ['page' => 'methodology'])->name('methodology');
Route::inertia('/limitations', 'Information', ['page' => 'limitations'])->name('limitations');
Route::inertia('/non-affiliation', 'Information', ['page' => 'affiliation'])->name('non-affiliation');
Route::inertia('/usage', 'Usage')->name('usage');
Route::get('/funding', FundingController::class)->name('funding');

Route::get('/policy/sources/{sourceId}', PolicyExplanationController::class)
    ->where('sourceId', '[A-Z][A-Z0-9-]{2,63}')
    ->middleware('throttle:policy-read')
    ->name('policy.sources.show');

Route::post('/api/build-imports/pob', PobImportController::class)
    ->middleware(['emergency:imports', 'verified.optional', 'throttle:imports'])
    ->name('build-imports.pob.store');

Route::delete('/api/build-imports/pob/{id}', DeletePobImportController::class)
    ->whereUuid('id')
    ->middleware(['verified.optional', 'throttle:deletion'])
    ->name('build-imports.pob.delete');

Route::post('/api/privacy-sessions', CreatePrivacySessionController::class)
    ->middleware('throttle:anonymous-sessions')
    ->name('privacy-sessions.create');

Route::middleware('verified.optional')->group(function (): void {
    Route::post('/api/analyses', SubmitAnalysisController::class)
        ->middleware(['emergency:imports', 'throttle:analysis-submit'])
        ->name('analyses.submit');
    Route::get('/api/analyses/{analysisId}', RetrieveAnalysisController::class)
        ->whereUuid('analysisId')->middleware('throttle:analysis-read')->name('analyses.show');
    Route::get('/api/analyses/{leftId}/compare/{rightId}', CompareAnalysesController::class)
        ->whereUuid(['leftId', 'rightId'])
        ->middleware('throttle:analysis-read')
        ->name('analyses.compare');
    Route::post('/api/analyses/{analysisId}/reanalyze', ReanalyzeController::class)
        ->whereUuid('analysisId')
        ->middleware(['emergency:rulesets', 'throttle:analysis-submit'])
        ->name('analyses.reanalyze');
    Route::get('/api/analyses/{analysisId}/provenance', AnalysisProvenanceController::class)
        ->whereUuid('analysisId')
        ->middleware('throttle:analysis-read')
        ->name('analyses.provenance');
    Route::get('/api/analyses/{analysisId}/export', ExportAnalysisController::class)
        ->whereUuid('analysisId')
        ->middleware('throttle:export')
        ->name('analyses.export');
    Route::delete('/api/builds/{buildId}', DeleteBuildController::class)
        ->whereUuid('buildId')
        ->middleware('throttle:deletion')
        ->name('builds.delete');
    Route::delete('/api/user-data', DeleteUserDataController::class)
        ->middleware('throttle:deletion')->name('user-data.delete');
});

Route::prefix('/admin/policy')
    ->middleware(['policy.admin', 'throttle:policy-admin'])
    ->group(function (): void {
        Route::get('/evidence', [PolicyEvidenceController::class, 'index']);
        Route::post('/evidence', [PolicyEvidenceController::class, 'store']);
        Route::post('/kill-switches', [PolicyKillSwitchController::class, 'store']);
    });
