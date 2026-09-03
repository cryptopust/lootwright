<?php

namespace Tests\Feature;

use App\Modules\Analysis\Infrastructure\PolicyGatedArtifactParser;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Domain\Shared\Game\GameEdition;
use Tests\TestCase;

final class ItemTextImportPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
    }

    public function test_policy_gated_workflow_normalizes_item_text_without_retaining_raw_artifact_or_logging_it(): void
    {
        $messages = [];
        Event::listen(MessageLogged::class, static function (MessageLogged $event) use (&$messages): void {
            $messages[] = $event->message.' '.json_encode($event->context, JSON_THROW_ON_ERROR);
        });
        $secretMarker = 'private-fixture-marker-8f97';
        $input = "Rarity: Rare\nFixture Ward\nAstral Plate\n--------\n+99 to maximum Life\n{$secretMarker}";
        $parsed = $this->app->make(PolicyGatedArtifactParser::class)->parse('item_text', $input, GameEdition::Poe1);
        $snapshot = json_decode($parsed->normalizedSnapshot, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('item-text-poe1', $parsed->adapterKey);
        self::assertSame('USER-ITEM-TEXT-001', $snapshot['canonical_build']['source_metadata']['source_id']);
        self::assertFalse($snapshot['canonical_build']['items'][0]['raw_text_retained']);
        self::assertArrayNotHasKey('item_text_untrusted', $snapshot['canonical_build']['items'][0]);
        self::assertStringNotContainsString($secretMarker, implode("\n", $messages));
        self::assertStringNotContainsString($input, implode("\n", $messages));
    }

    public function test_item_text_import_fails_closed_when_policy_evidence_is_missing(): void
    {
        $sourceVersionId = DB::table('policy_data_source_versions')
            ->where('source_id', 'USER-ITEM-TEXT-001')
            ->where('version', '1.0.0')
            ->value('id');
        DB::table('policy_permission_evidence')->where('source_version_id', $sourceVersionId)->delete();

        $this->expectException(PolicyBlocked::class);
        $this->app->make(PolicyGatedArtifactParser::class)->parse(
            'item_text',
            "Rarity: Normal\nFixture",
            GameEdition::Poe1,
        );
    }
}
