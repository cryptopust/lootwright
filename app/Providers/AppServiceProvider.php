<?php

namespace App\Providers;

use App\Modules\Analysis\Infrastructure\DatabaseAnalysisPolicyGate;
use App\Modules\Analysis\Infrastructure\EncryptedArtifactStorage;
use App\Modules\Analysis\Infrastructure\LaravelIdentifierGenerator;
use App\Modules\Analysis\Infrastructure\LaravelTransactionManager;
use App\Modules\Analysis\Infrastructure\LaravelWorkflowDispatcher;
use App\Modules\Analysis\Infrastructure\PolicyGatedArtifactParser;
use App\Modules\Analysis\Infrastructure\UnavailableDeterministicAnalysisEngine;
use App\Modules\Analysis\Persistence\PostgresWorkflowRepository;
use App\Modules\BuildIntake\PobImportStore;
use App\Modules\PolicyProvenance\DatabaseCapabilityPolicy;
use App\Modules\TradePlanning\DatabaseManualTradeRecipePolicy;
use App\Modules\TradePlanning\EditionManualTradeRecipeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipeGenerator;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipePolicy;
use Lootwright\Application\Workflow\Ports\AnalysisPolicyGate;
use Lootwright\Application\Workflow\Ports\ArtifactParser;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;
use Lootwright\Application\Workflow\Ports\SupplementalUserDataEraser;
use Lootwright\Application\Workflow\Ports\TransactionManager;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Domain\PolicyProvenance\PolicyEvaluator;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Parser;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Parser;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\SafeXmlParser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PolicyEvaluator::class);
        $this->app->bind(CapabilityPolicy::class, DatabaseCapabilityPolicy::class);
        $this->app->bind(WorkflowRepository::class, PostgresWorkflowRepository::class);
        $this->app->bind(ArtifactStorage::class, EncryptedArtifactStorage::class);
        $this->app->bind(WorkflowDispatcher::class, LaravelWorkflowDispatcher::class);
        $this->app->bind(IdentifierGenerator::class, LaravelIdentifierGenerator::class);
        $this->app->bind(SupplementalUserDataEraser::class, PobImportStore::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(ArtifactParser::class, PolicyGatedArtifactParser::class);
        $this->app->bind(DeterministicAnalysisEngine::class, UnavailableDeterministicAnalysisEngine::class);
        $this->app->bind(AnalysisPolicyGate::class, DatabaseAnalysisPolicyGate::class);
        $this->app->bind(ManualTradeRecipeGenerator::class, EditionManualTradeRecipeGenerator::class);
        $this->app->bind(ManualTradeRecipePolicy::class, DatabaseManualTradeRecipePolicy::class);
        $this->app->singleton(PobImportCoordinator::class, static fn (): PobImportCoordinator => new PobImportCoordinator(
            new PobEnvelopeDecoder,
            new SafeXmlParser,
            [
                new Pob1Parser(new Pob1Normalizer),
                new Pob2Parser(new Pob2Normalizer),
            ],
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
            : null,
        );
    }
}
