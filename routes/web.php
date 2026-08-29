<?php

use App\Http\Controllers\Admin\AdminAiControlController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AdminReleaseController;
use App\Http\Controllers\Admin\AdminSourceImportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PolicyEvidenceController;
use App\Http\Controllers\Admin\PolicyKillSwitchController;
use App\Http\Controllers\AiFollowUpController;
use App\Http\Controllers\AnalysisDraftController;
use App\Http\Controllers\AnalysisProvenanceController;
use App\Http\Controllers\Catalog\CharacterOptionsController;
use App\Http\Controllers\CompareAnalysesController;
use App\Http\Controllers\CreatePrivacySessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeleteBuildController;
use App\Http\Controllers\DeletePobImportController;
use App\Http\Controllers\DeleteUserDataController;
use App\Http\Controllers\ExportAnalysisController;
use App\Http\Controllers\FundingController;
use App\Http\Controllers\MemberAnalysisController;
use App\Http\Controllers\PobImportController;
use App\Http\Controllers\PolicyExplanationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReanalyzeController;
use App\Http\Controllers\RetrieveAnalysisController;
use App\Http\Controllers\SubmitAnalysisController;
use App\Http\Controllers\SubmitWizardAnalysisController;
use App\Http\Controllers\UsageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Landing')->name('home');
Route::inertia('/analyses/new', 'Analysis/New')->name('analyses.new');
if (app()->environment(['local', 'testing'])) {
    Route::inertia('/style-guide', 'StyleGuide')->name('style-guide');
}
Route::get('/api/catalog/{game}/character-options', CharacterOptionsController::class)
    ->where('game', 'poe1')
    ->middleware('throttle:60,1')
    ->name('catalog.characters');
if (app()->environment(['local', 'testing'])) {
    Route::inertia('/analyses/demo/import', 'Analysis/ImportReview')->name('analyses.demo.import');
    Route::get('/analyses/demo/{section}', static fn (string $section) => Inertia::render('Analysis/Workspace', [
        'section' => $section,
        'externalLinksEnabled' => (bool) config('security.emergency.external_links'),
    ]))
        ->where('section', 'overview|findings|upgrades|trade|provenance|states')
        ->name('analyses.demo.workspace');
}

Route::inertia('/privacy', 'Information', ['page' => 'privacy'])->name('privacy');
Route::inertia('/data-deletion', 'Information', ['page' => 'deletion'])->name('data-deletion');
Route::inertia('/methodology', 'Information', ['page' => 'methodology'])->name('methodology');
Route::inertia('/limitations', 'Information', ['page' => 'limitations'])->name('limitations');
Route::inertia('/non-affiliation', 'Information', ['page' => 'affiliation'])->name('non-affiliation');
Route::get('/usage', UsageController::class)->name('usage');
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
    Route::post('/api/analyses/{analysisId}/ai-follow-up', AiFollowUpController::class)
        ->whereUuid('analysisId')->middleware('throttle:analysis-submit')->name('analyses.ai-follow-up');
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

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::middleware('verified')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/analyses', [MemberAnalysisController::class, 'index'])->name('member.analyses.index');
        Route::get('/analyses/{analysis}', [MemberAnalysisController::class, 'show'])->whereUuid('analysis')->name('member.analyses.show');
        Route::delete('/analyses/{analysis}', [MemberAnalysisController::class, 'destroy'])->whereUuid('analysis')->middleware('password.confirm')->name('member.analyses.destroy');
        Route::post('/api/analyses/wizard', SubmitWizardAnalysisController::class)->middleware('throttle:analysis-submit')->name('analyses.wizard.submit');
        Route::get('/api/analysis-draft', [AnalysisDraftController::class, 'show'])->name('draft.show');
        Route::put('/api/analysis-draft', [AnalysisDraftController::class, 'store'])->name('draft.store');
        Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');
        Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');
        Route::get('/profile/privacy', [ProfileController::class, 'privacy'])->name('profile.privacy');
    });
});

Route::prefix('admin')->middleware(['auth', 'active', 'verified', 'role:admin,super_admin', 'admin.2fa'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::put('/users/{user}/status', [AdminUserController::class, 'status'])->middleware('password.confirm')->name('admin.users.status');
    Route::put('/users/{user}/role', [AdminUserController::class, 'role'])->middleware(['password.confirm', 'role:super_admin'])->name('admin.users.role');
    Route::get('/audit-log', [AdminPageController::class, 'audit'])->name('admin.audit');
    Route::get('/catalog', [AdminPageController::class, 'catalog'])->name('admin.catalog');
    Route::get('/system', [AdminPageController::class, 'system'])->name('admin.system');
    Route::get('/release', AdminReleaseController::class)->name('admin.release');
    Route::post('/sources/import', AdminSourceImportController::class)
        ->middleware(['password.confirm', 'role:super_admin', 'throttle:source-import-admin'])
        ->name('admin.sources.import');
    Route::put('/ai/settings', [AdminAiControlController::class, 'settings'])
        ->middleware(['password.confirm', 'role:super_admin', 'throttle:policy-admin'])
        ->name('admin.ai.settings');
    Route::put('/users/{user}/ai-quota', [AdminAiControlController::class, 'userQuota'])
        ->middleware(['password.confirm', 'role:super_admin', 'throttle:policy-admin'])
        ->name('admin.users.ai-quota');
});

Route::prefix('/admin/policy')
    ->middleware(['policy.admin', 'throttle:policy-admin'])
    ->group(function (): void {
        Route::get('/evidence', [PolicyEvidenceController::class, 'index']);
        Route::post('/evidence', [PolicyEvidenceController::class, 'store']);
        Route::post('/kill-switches', [PolicyKillSwitchController::class, 'store']);
    });
