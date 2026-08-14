<?php

namespace App\Providers;

use App\Modules\PolicyProvenance\DatabaseCapabilityPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lootwright\Application\BuildIntake\PobImportService;
use Lootwright\Domain\PolicyProvenance\PolicyEvaluator;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Parser;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Parser;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
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
        $this->app->singleton(PobImportService::class, static fn (): PobImportService => new PobImportService(
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
