<?php

namespace App\Providers;

use App\Modules\AI\AiRequestContextFactory;
use App\Modules\AI\DatabaseAiBudget;
use App\Modules\AI\DatabaseAiCircuitBreaker;
use App\Modules\AI\DatabaseAiExecutionPolicy;
use App\Modules\AI\DatabaseAiRuntimePolicy;
use App\Modules\AI\DatabaseAiTelemetry;
use App\Modules\AI\DatabaseAiUserDataEraser;
use App\Modules\AI\LaravelAiResponseCache;
use App\Modules\AI\OpenAi\LaravelOpenAiHttpTransport;
use App\Modules\AI\OpenAi\OpenAiHttpTransport;
use App\Modules\AI\OpenAi\OpenAiResponsesProvider;
use App\Modules\AI\PostgresAnalysisExplanationRepository;
use App\Modules\Analysis\Infrastructure\CompositeSupplementalUserDataEraser;
use App\Modules\Analysis\Infrastructure\DatabaseAnalysisPolicyGate;
use App\Modules\Analysis\Infrastructure\EncryptedArtifactStorage;
use App\Modules\Analysis\Infrastructure\LaravelIdentifierGenerator;
use App\Modules\Analysis\Infrastructure\LaravelTransactionManager;
use App\Modules\Analysis\Infrastructure\LaravelWorkflowDispatcher;
use App\Modules\Analysis\Infrastructure\PolicyGatedArtifactParser;
use App\Modules\Analysis\Infrastructure\ProductionEditionDeterministicAnalysisEngine;
use App\Modules\Analysis\Persistence\PostgresWorkflowRepository;
use App\Modules\BuildIntake\PolicyGatedItemTextImporter;
use App\Modules\ExternalSources\DatabaseSourceImportStaging;
use App\Modules\ExternalSources\DatabaseSourceRegistry;
use App\Modules\ExternalSources\DatabaseSourceUpdateObserver;
use App\Modules\ExternalSources\DisabledOfficialTradeSearchProvider;
use App\Modules\ExternalSources\FixedExternalSourceAdapterCatalog;
use App\Modules\ExternalSources\Ggg\DisabledOfficialGggApiSourceAdapter;
use App\Modules\ExternalSources\Ggg\GggPassiveTreeSourceAdapter;
use App\Modules\ExternalSources\Poe2\DisabledPoe2DatasetAdapter;
use App\Modules\ExternalSources\Poe2\Poe2DatasetImporter;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaEconomyClient;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaNormalizer;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaPolicyGate;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaSourceAdapter;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaSyncService;
use App\Modules\ExternalSources\PoeWiki\DisabledPoeWikiCargoAdapter;
use App\Modules\Funding\PolicyGatedFundingStatusProvider;
use App\Modules\Identity\LaravelSecretGenerator;
use App\Modules\Identity\PostgresPrivacySessionRepository;
use App\Modules\Market\LaravelMarketEstimateCache;
use App\Modules\Market\PostgresPoeNinjaObservationRepository;
use App\Modules\PolicyProvenance\DatabaseCapabilityPolicy;
use App\Modules\Rulesets\DatabaseCanonicalDataConflictRecorder;
use App\Modules\Rulesets\DatabaseSourceAuthorityRegistry;
use App\Modules\Rulesets\DatabaseSourceGovernancePolicy;
use App\Modules\Rulesets\PostgresCanonicalGameDataRepository;
use App\Modules\Rulesets\PostgresDataCoverageReporter;
use App\Modules\Rulesets\PostgresGovernedRulesetRepository;
use App\Modules\Rulesets\PostgresRulesetRepository;
use App\Modules\Rulesets\PostgresRulesetResolver;
use App\Modules\TradePlanning\DatabaseManualTradeRecipePolicy;
use App\Modules\TradePlanning\EditionManualTradeRecipeGenerator;
use App\Security\OutboundRequestGuard;
use App\Security\RateLimitKey;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lootwright\Application\AIGateway\DTO\AiGatewayConfiguration;
use Lootwright\Application\AIGateway\Ports\AiBudget;
use Lootwright\Application\AIGateway\Ports\AiCircuitBreaker;
use Lootwright\Application\AIGateway\Ports\AiExecutionPolicy;
use Lootwright\Application\AIGateway\Ports\AiGateway;
use Lootwright\Application\AIGateway\Ports\AiResponseCache;
use Lootwright\Application\AIGateway\Ports\AiRuntimePolicy;
use Lootwright\Application\AIGateway\Ports\AiTelemetry;
use Lootwright\Application\AIGateway\Ports\AnalysisExplanationRepository;
use Lootwright\Application\AIGateway\Ports\IntentInterpreter;
use Lootwright\Application\AIGateway\Ports\RecommendationExplainer;
use Lootwright\Application\AIGateway\Ports\StructuredAiProvider;
use Lootwright\Application\AIGateway\Schema\StrictJsonSchemaValidator;
use Lootwright\Application\AIGateway\Services\ProviderNeutralAiGateway;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;
use Lootwright\Application\ExternalSources\Ports\OfficialTradeSearchProvider;
use Lootwright\Application\ExternalSources\Ports\SourceImportStaging;
use Lootwright\Application\ExternalSources\Ports\SourceRegistry;
use Lootwright\Application\ExternalSources\Ports\SourceUpdateObserver;
use Lootwright\Application\Funding\Ports\FundingStatusProvider;
use Lootwright\Application\GameData\Ports\CanonicalDataConflictRecorder;
use Lootwright\Application\GameData\Ports\DataCoverageReporter;
use Lootwright\Application\GameData\Ports\SourceAuthorityRegistry;
use Lootwright\Application\GameData\SourceAuthorityResolver;
use Lootwright\Application\Identity\Ports\PrivacySessionRepository;
use Lootwright\Application\Identity\Ports\SecretGenerator;
use Lootwright\Application\Market\Ports\MarketEstimateCache;
use Lootwright\Application\Market\Ports\MarketObservationRepository;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Application\Rulesets\Ports\GovernedRulesetRepository;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Application\TradePlanning\ModifierMatcher;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipeGenerator;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipePolicy;
use Lootwright\Application\TradePlanning\TradeRecipeBuilder;
use Lootwright\Application\Workflow\Ports\AnalysisDocumentRepository;
use Lootwright\Application\Workflow\Ports\AnalysisPolicyGate;
use Lootwright\Application\Workflow\Ports\ArtifactParser;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\BuildLifecycleRepository;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;
use Lootwright\Application\Workflow\Ports\SupplementalUserDataEraser;
use Lootwright\Application\Workflow\Ports\TransactionManager;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\PolicyProvenance\PolicyEvaluator;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\Recommendations\DeterministicUpgradePlanner;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\UpgradePriorityScorer;
use Lootwright\Domain\Rulesets\Ports\ActiveRulesetResolver;
use Lootwright\Domain\Rulesets\Ports\RulesetRepository;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine as Poe1CoreAnalysisEngine;
use Lootwright\GameAdapters\PoE1\BuildImport\Poe1BuildImporter;
use Lootwright\GameAdapters\PoE1\ItemText\Poe1ItemTextImporter;
use Lootwright\GameAdapters\PoE1\PassiveTree\PassiveTreeNormalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Parser;
use Lootwright\GameAdapters\PoE1\Recommendations\Poe1UpgradeCandidateFactory;
use Lootwright\GameAdapters\PoE1\Rulesets\Poe1RulesetLoader;
use Lootwright\GameAdapters\PoE2\BuildImport\Poe2BuildImporter;
use Lootwright\GameAdapters\PoE2\ItemText\Poe2ItemTextImporter;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Parser;
use Lootwright\GameAdapters\PoE2\Recommendations\Poe2UpgradeCandidateFactory;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2RulesetLoader;
use Lootwright\GameAdapters\Shared\BuildImport\BuildImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\SafeXmlParser;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PolicyEvaluator::class);
        $this->app->bind(CapabilityPolicy::class, DatabaseCapabilityPolicy::class);
        $this->app->bind(SourceGovernancePolicy::class, DatabaseSourceGovernancePolicy::class);
        $this->app->bind(SourceRegistry::class, DatabaseSourceRegistry::class);
        $this->app->bind(SourceImportStaging::class, DatabaseSourceImportStaging::class);
        $this->app->bind(SourceUpdateObserver::class, DatabaseSourceUpdateObserver::class);
        $this->app->singleton(ExternalSourceAdapterCatalog::class, FixedExternalSourceAdapterCatalog::class);
        $this->app->bind(GovernedRulesetRepository::class, PostgresGovernedRulesetRepository::class);
        $this->app->bind(RulesetResolver::class, PostgresRulesetResolver::class);
        $this->app->bind(ActiveRulesetResolver::class, PostgresRulesetResolver::class);
        $this->app->bind(RulesetRepository::class, PostgresRulesetRepository::class);
        $this->app->bind(GameDataRepository::class, PostgresCanonicalGameDataRepository::class);
        $this->app->bind(SourceAuthorityRegistry::class, DatabaseSourceAuthorityRegistry::class);
        $this->app->bind(CanonicalDataConflictRecorder::class, DatabaseCanonicalDataConflictRecorder::class);
        $this->app->bind(DataCoverageReporter::class, PostgresDataCoverageReporter::class);
        $this->app->singleton(SourceAuthorityResolver::class, static fn (): SourceAuthorityResolver => new SourceAuthorityResolver(
            (array) config('game-data.authority_precedence', []),
        ));
        $this->app->singleton(PassiveTreeNormalizer::class);
        $this->app->bind(WorkflowRepository::class, PostgresWorkflowRepository::class);
        $this->app->bind(AnalysisDocumentRepository::class, PostgresWorkflowRepository::class);
        $this->app->bind(BuildLifecycleRepository::class, PostgresWorkflowRepository::class);
        $this->app->bind(ArtifactStorage::class, EncryptedArtifactStorage::class);
        $this->app->bind(WorkflowDispatcher::class, LaravelWorkflowDispatcher::class);
        $this->app->bind(IdentifierGenerator::class, LaravelIdentifierGenerator::class);
        $this->app->bind(SupplementalUserDataEraser::class, CompositeSupplementalUserDataEraser::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(ArtifactParser::class, PolicyGatedArtifactParser::class);
        $this->app->singleton(Poe1CoreAnalysisEngine::class);
        $this->app->singleton(Poe1RulesetLoader::class);
        $this->app->singleton(Poe2RulesetLoader::class);
        $this->app->bind(DeterministicAnalysisEngine::class, ProductionEditionDeterministicAnalysisEngine::class);
        $this->app->singleton(UpgradePlanner::class, static fn (): UpgradePlanner => new DeterministicUpgradePlanner([
            new Poe1UpgradeCandidateFactory(new UpgradePriorityScorer),
            new Poe2UpgradeCandidateFactory(new UpgradePriorityScorer),
        ]));
        $this->app->bind(AnalysisPolicyGate::class, DatabaseAnalysisPolicyGate::class);
        $this->app->bind(ManualTradeRecipeGenerator::class, EditionManualTradeRecipeGenerator::class);
        $this->app->bind(ManualTradeRecipePolicy::class, DatabaseManualTradeRecipePolicy::class);
        $this->app->singleton(ModifierMatcher::class, static fn (): ModifierMatcher => new ModifierMatcher(app(GameDataRepository::class)));
        $this->app->singleton(TradeRecipeBuilder::class, static fn (): TradeRecipeBuilder => new TradeRecipeBuilder(app(ModifierMatcher::class)));
        $this->app->bind(MarketObservationRepository::class, PostgresPoeNinjaObservationRepository::class);
        $this->app->singleton(MarketEstimateCache::class, static fn ($app): MarketEstimateCache => new LaravelMarketEstimateCache($app->make('cache.store')));
        $this->app->bind(PrivacySessionRepository::class, PostgresPrivacySessionRepository::class);
        $this->app->bind(SecretGenerator::class, LaravelSecretGenerator::class);
        $this->app->bind(FundingStatusProvider::class, PolicyGatedFundingStatusProvider::class);
        $this->app->singleton(PoeNinjaEconomyClient::class, static fn (): PoeNinjaEconomyClient => new PoeNinjaEconomyClient(app(OutboundRequestGuard::class)));
        $this->app->singleton(PoeNinjaNormalizer::class);
        $this->app->singleton(PoeNinjaPolicyGate::class);
        $this->app->singleton(PoeNinjaSyncService::class);
        $this->app->singleton(GggPassiveTreeSourceAdapter::class);
        $this->app->singleton(PoeNinjaSourceAdapter::class);
        $this->app->singleton(DisabledOfficialGggApiSourceAdapter::class);
        $this->app->singleton(DisabledPoe2DatasetAdapter::class);
        $this->app->singleton(Poe2DatasetImporter::class);
        $this->app->singleton(DisabledPoeWikiCargoAdapter::class);
        $this->app->bind(OfficialTradeSearchProvider::class, DisabledOfficialTradeSearchProvider::class);
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
            app(OutboundRequestGuard::class),
        ));
        $this->app->singleton(OutboundRequestGuard::class, static fn (): OutboundRequestGuard => new OutboundRequestGuard(
            (bool) config('security.outbound.enabled'),
            (array) config('security.outbound.targets', []),
        ));
        $this->app->singleton(StructuredAiProvider::class, static fn ($app): StructuredAiProvider => new OpenAiResponsesProvider(
            $app->make(OpenAiHttpTransport::class),
            (int) config('ai.max_retries'),
            (int) config('ai.retry_base_delay_ms'),
            (int) config('ai.retry_max_delay_ms'),
        ));
        $this->app->bind(AiExecutionPolicy::class, DatabaseAiExecutionPolicy::class);
        $this->app->bind(AiRuntimePolicy::class, DatabaseAiRuntimePolicy::class);
        $this->app->singleton(AiCircuitBreaker::class, static fn (): AiCircuitBreaker => new DatabaseAiCircuitBreaker(
            (int) config('ai.circuit_failure_threshold'),
            (int) config('ai.circuit_cooldown_seconds'),
        ));
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
        $this->app->bind(AnalysisExplanationRepository::class, PostgresAnalysisExplanationRepository::class);
        $this->app->singleton(AiGateway::class, static fn ($app): AiGateway => new ProviderNeutralAiGateway(
            $app->make(AiGatewayConfiguration::class),
            $app->make(StructuredAiProvider::class),
            $app->make(AiExecutionPolicy::class),
            $app->make(AiBudget::class),
            $app->make(AiResponseCache::class),
            $app->make(AiTelemetry::class),
            new StrictJsonSchemaValidator,
            $app->make(AiRuntimePolicy::class),
            $app->make(AiCircuitBreaker::class),
        ));
        $this->app->alias(AiGateway::class, IntentInterpreter::class);
        $this->app->alias(AiGateway::class, RecommendationExplainer::class);
        $this->app->singleton(PobImportCoordinator::class, static fn (): PobImportCoordinator => new PobImportCoordinator(
            new PobEnvelopeDecoder,
            new SafeXmlParser,
            [
                new Pob1Parser(new Pob1Normalizer),
                new Pob2Parser(new Pob2Normalizer),
            ],
        ));
        $this->app->singleton(BuildImportCoordinator::class, static fn ($app): BuildImportCoordinator => new BuildImportCoordinator([
            new Poe1BuildImporter($app->make(PobImportCoordinator::class), new Poe1ItemTextImporter),
            new Poe2BuildImporter($app->make(PobImportCoordinator::class), new Poe2ItemTextImporter),
        ]));
        $this->app->singleton(PolicyGatedItemTextImporter::class, static fn ($app): PolicyGatedItemTextImporter => new PolicyGatedItemTextImporter(
            $app->make(BuildImportCoordinator::class),
            $app->make(DecideCapability::class),
            $app->make(LoggerInterface::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(static function (JobFailed $event): void {
            // Emit a provider-neutral, secret-free operational signal. Cloud
            // log routing can alert on this event without coupling the domain
            // to a commercial monitoring service.
            Log::error('queue_job_failed', [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job_hash' => hash('sha256', $event->job->resolveName()),
                'exception_type' => $event->exception::class,
            ]);
        });

        if (app()->isProduction() && config('external-sources.poe_ninja.enabled') && trim((string) config('external-sources.poe_ninja.contact')) === '') {
            throw new \RuntimeException('POE_NINJA_CONTACT is required when both poe.ninja source switches are enabled in production.');
        }
        if (app()->isProduction() && config('source-governance.ggg_passive_tree.enabled') && trim((string) config('source-governance.ggg_passive_tree.contact')) === '') {
            throw new \RuntimeException('GGG_PASSIVE_TREE_CONTACT is required when the GGG passive-tree importer is enabled in production.');
        }
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        $this->configureRateLimits();

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

    private function configureRateLimits(): void
    {
        RateLimiter::for('authentication', static fn (Request $request): array => self::limits($request, 'authentication', 5, 25));
        RateLimiter::for('anonymous-sessions', static fn (Request $request): array => self::limits($request, 'anonymous-sessions', 3, 20));
        RateLimiter::for('imports', static fn (Request $request): array => self::limits($request, 'imports', 10, 100));
        RateLimiter::for('analysis-submit', static fn (Request $request): array => self::limits($request, 'analysis-submit', 6, 60));
        RateLimiter::for('analysis-read', static fn (Request $request): array => self::limits($request, 'analysis-read', 60, 1_000));
        RateLimiter::for('ai', static fn (Request $request): array => self::limits($request, 'ai', 5, 50));
        RateLimiter::for('export', static fn (Request $request): array => self::limits($request, 'export', 5, 30));
        RateLimiter::for('deletion', static fn (Request $request): array => self::limits($request, 'deletion', 3, 10));
        RateLimiter::for('policy-read', static fn (Request $request): array => self::limits($request, 'policy-read', 30, 300));
        RateLimiter::for('policy-admin', static fn (Request $request): array => self::limits($request, 'policy-admin', 10, 50));
        RateLimiter::for('source-import-admin', static fn (Request $request): array => self::limits($request, 'source-import-admin', 2, 10));
    }

    /** @return list<Limit> */
    private static function limits(Request $request, string $scope, int $perMinute, int $perDay): array
    {
        $key = RateLimitKey::for($request, $scope);

        return [
            Limit::perMinute($perMinute)->by($key.':minute'),
            Limit::perDay($perDay)->by($key.':day'),
        ];
    }
}
