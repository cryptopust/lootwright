<?php

namespace Tests\Feature;

use App\Modules\TradePlanning\DatabaseManualTradeRecipePolicy;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lootwright\Application\Workflow\UseCases\ExplainPolicyDecision;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\Shared\Game\GameEdition;
use RuntimeException;
use Tests\Support\DomainFixtures;
use Tests\TestCase;

class ManualTradePolicyGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
    }

    public function test_local_manual_generation_and_one_generic_homepage_link_are_allowed_and_audited(): void
    {
        $this->app->make(DatabaseManualTradeRecipePolicy::class)->authorize(
            GameEdition::Poe1,
            DomainFixtures::ruleset(GameEdition::Poe1),
        );

        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'LOOTWRIGHT-MANUAL-TRADE',
            'capability' => 'derivative_analysis',
            'operation' => 'trade.manual_recipe.generate',
            'decision' => 'allow',
        ]);
        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'LOOTWRIGHT-MANUAL-TRADE',
            'capability' => 'link_out',
            'operation' => 'trade.homepage.link',
            'decision' => 'allow',
        ]);
    }

    public function test_live_trade_endpoints_listing_fetch_and_encoded_url_generation_remain_denied(): void
    {
        foreach (['get:/api/trade/search', 'get:/api/trade/fetch', 'family:/api/trade/data'] as $operation) {
            $decision = $this->decision(
                Capability::LiveFetch,
                $operation,
                'GGG-UNDOCUMENTED-TRADE',
                '2026-08-14',
            );
            self::assertSame(PolicyDecision::Deny, $decision->decision, $operation);
            self::assertFalse($decision->permitsExecution());
        }

        $encoded = $this->decision(
            Capability::LinkOut,
            'trade.encoded_url.generate',
            'LOOTWRIGHT-MANUAL-TRADE',
            '1.0.0',
        );
        $listings = $this->decision(
            Capability::LiveFetch,
            'trade.listings.fetch',
            'LOOTWRIGHT-MANUAL-TRADE',
            '1.0.0',
        );

        self::assertSame(PolicyDecision::Deny, $encoded->decision);
        self::assertSame(PolicyDecision::Deny, $listings->decision);
        $this->assertDatabaseHas('policy_decision_audits', [
            'operation' => 'trade.encoded_url.generate',
            'decision' => 'deny',
        ]);
        $this->assertDatabaseHas('policy_decision_audits', [
            'operation' => 'trade.listings.fetch',
            'decision' => 'deny',
        ]);
    }

    public function test_trade_planning_implementation_has_no_http_or_browser_capability(): void
    {
        $roots = [
            base_path('src/Application/TradePlanning'),
            base_path('src/GameAdapters/PoE1/TradePlanning'),
            base_path('src/GameAdapters/PoE2/TradePlanning'),
            base_path('app/Modules/TradePlanning'),
        ];

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($iterator as $file) {
                if (! $file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname()) ?: '';
                self::assertStringNotContainsString('Http::', $content);
                self::assertStringNotContainsString('GuzzleHttp', $content);
                self::assertStringNotContainsString('Browser', $content);
                self::assertStringNotContainsString('/api/trade/', $content);
                self::assertStringNotContainsString('POESESSID', $content);
            }
        }
    }

    private function decision(
        Capability $capability,
        string $operation,
        string $sourceId,
        string $sourceVersion,
    ): CapabilityDecision {
        $timestamp = RetrievedAt::from('2026-08-14T13:20:00Z')->value();

        if (! $timestamp instanceof RetrievedAt) {
            throw new RuntimeException('Expected a policy timestamp.');
        }

        $request = CapabilityRequest::create(
            $capability,
            $operation,
            $sourceId,
            $sourceVersion,
            $timestamp,
        )->value();

        if (! $request instanceof CapabilityRequest) {
            throw new RuntimeException('Expected a capability request.');
        }

        return $this->app->make(ExplainPolicyDecision::class)->handle($request);
    }
}
