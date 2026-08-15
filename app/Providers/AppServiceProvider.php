<?php

namespace App\Providers;

use App\Modules\AI\AiRequestContextFactory;
use App\Modules\AI\DatabaseAiBudget;
use App\Modules\AI\DatabaseAiExecutionPolicy;
use App\Modules\AI\DatabaseAiTelemetry;
use App\Modules\AI\DatabaseAiUserDataEraser;
use App\Modules\AI\LaravelAiResponseCache;
use App\Modules\AI\OpenAi\LaravelOpenAiHttpTransport;
use App\Modules\AI\OpenAi\OpenAiHttpTransport;
use App\Modules\AI\OpenAi\OpenAiResponsesProvider;
use App\Modules\Analysis\Infrastructure\CompositeSupplementalUserDataEraser;
use App\Modules\Analysis\Infrastructure\DatabaseAnalysisPolicyGate;
use App\Modules\Analysis\Infrastructure\EncryptedArtifactStorage;
use App\Modules\Analysis\Infrastructure\LaravelIdentifierGenerator;
use App\Modules\Analysis\Infrastructure\LaravelTransactionManager;
use App\Modules\Analysis\Infrastructure\LaravelWorkflowDispatcher;
use App\Modules\Analysis\Infrastructure\PolicyGatedArtifactParser;
use App\Modules\Analysis\Infrastructure\UnavailableDeterministicAnalysisEngine;
use App\Modules\Analysis\Persistence\PostgresWorkflowRepository;
use App\Modules\PolicyProvenance\DatabaseCapabilityPolicy;
use App\Modules\TradePlanning\DatabaseManualTradeRecipePolicy;
use App\Modules\TradePlanning\EditionManualTradeRecipeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lootwright\Application\AIGateway\DTO\AiGatewayConfiguration;
use Lootwright\Application\AIGateway\Ports\AiBudget;
use Lootwright\Application\AIGateway\Ports\AiExecutionPolicy;
use Lootwright\Application\AIGateway\Ports\AiGateway;
use Lootwright\Application\AIGateway\Ports\AiResponseCache;
use Lootwright\Application\AIGateway\Ports\AiTelemetry;
use Lootwright\Application\AIGateway\Ports\StructuredAiProvider;
use Lootwright\Application\AIGateway\Services\ProviderNeutralAiGateway;
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
        $this->app->bind(SupplementalUserDataEraser::class, CompositeSupplementalUserDataEraser::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(ArtifactParser::class, PolicyGatedArtifactParser::class);
        $this->app->bind(DeterministicAnalysisEngine::class, UnavailableDeterministicAnalysisEngine::class);
        $this->app->bind(AnalysisPolicyGate::class, DatabaseAnalysisPolicyGate::class);
        $this->app->bind(ManualTradeRecipeGenerator::class, EditionManualTradeRecipeGenerator::class);
        $this->app->bind(ManualTradeRecipePolicy::class, DatabaseManualTradeRecipePolicy::class);
        $this->app->singleton(AiGatewayConfiguration::class, static function (): AiGatewayConfiguration {
            $prices = config('ai.prices_micro_usd_per_million');

            return new AiGatewayConfiguration(
                (bool) config('ai.enabled'),
                (string) config('ai.intent_model'),
                (string) config('ai.explanation_model'),
                (int) config('ai.max_input_tokens'),
                (int) config('ai.intent_max_output_tokens'),
                (int) config('ai.explanation_max_output_tokens'),
                (int) config('ai.clarification_threshold_basis_points'),
                (string) config('ai.prompt_template_version'),
                (int) $prices['input'],
                (int) $prices['cached_input'],
                (int) $prices['output'],
                hash('sha256', (string) config('app.key')),
            );
        });
        $this->app->singleton(AiRequestContextFactory::class, static fn (): AiRequestContextFactory => new AiRequestContextFactory(
            hash('sha256', (string) config('app.key')),
        ));
        $this->app->singleton(OpenAiHttpTransport::class, static fn (): OpenAiHttpTransport => new LaravelOpenAiHttpTransport(
            (string) config('ai.api_key'),
            (int) config('ai.timeout_seconds'),
            (int) config('ai.connect_timeout_seconds'),
        ));
        $this->app->singleton(StructuredAiProvider::class, static fn ($app): StructuredAiProvider => new OpenAiResponsesProvider(
            $app->make(OpenAiHttpTransport::class),
            (int) config('ai.max_retries'),
            (int) config('ai.retry_base_delay_ms'),
            (int) config('ai.retry_max_delay_ms'),
        ));
        $this->app->bind(AiExecutionPolicy::class, DatabaseAiExecutionPolicy::class);
        $this->app->singleton(AiBudget::class, static function (): AiBudget {
            $budgets = config('ai.budgets_micro_usd');

            return new DatabaseAiBudget(
                (int) $budgets['per_user_daily'],
                (int) $budgets['per_ip_daily'],
                (int) $budgets['global_daily'],
                (int) $budgets['global_monthly'],
            );
        });
        $this->app->singleton(AiResponseCache::class, static fn ($app): AiResponseCache => new LaravelAiResponseCache(
            $app->make('cache.store'),
            (int) config('ai.cache_ttl_seconds'),
        ));
        $this->app->singleton(DatabaseAiUserDataEraser::class, static fn ($app): DatabaseAiUserDataEraser => new DatabaseAiUserDataEraser(
            hash('sha256', (string) config('app.key')),
            $app->make(AiResponseCache::class),
        ));
        $this->app->bind(AiTelemetry::class, DatabaseAiTelemetry::class);
        $this->app->bind(AiGateway::class, ProviderNeutralAiGateway::class);
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
