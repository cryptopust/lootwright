<?php

namespace Tests\Unit\Application;

use Lootwright\Application\AIGateway\DTO\AiGatewayOutcome;
use Lootwright\Application\AIGateway\DTO\AiRequestContext;
use Lootwright\Application\AIGateway\DTO\BuildIntentCandidate;
use Lootwright\Application\AIGateway\DTO\ClarificationSet;
use Lootwright\Application\AIGateway\DTO\GatewayExplanationRequest;
use Lootwright\Application\AIGateway\DTO\IntentVocabulary;
use Lootwright\Application\AIGateway\DTO\NaturalLanguageIntentRequest;
use Lootwright\Application\AIGateway\Ports\AiGateway;
use Lootwright\Application\AIGateway\Services\ObtainBuildIntent;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Locale;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class ObtainBuildIntentTest extends TestCase
{
    public function test_schema_candidate_is_resolved_into_domain_intent_without_inventing_identifiers(): void
    {
        $candidate = new BuildIntentCandidate(
            GameEdition::Poe1,
            'fixture.content',
            'fixture.style',
            [['code' => 'fixture.constraint', 'value' => 'bounded', 'priority' => 'high']],
            8_500,
        );
        $resolution = (new ObtainBuildIntent(new IntentOutcomeGateway(
            new AiGatewayOutcome('provider', $candidate),
        )))->handle($this->request());

        self::assertNull($resolution->clarifications);
        self::assertNotNull($resolution->intent);
        self::assertSame(GameEdition::Poe1, $resolution->intent->goal->edition);
        self::assertSame('fixture.content', $resolution->intent->goal->contentGoal->value);
        self::assertSame(UpgradePriority::High, $resolution->intent->goal->constraints[0]->priority);
        self::assertSame('provider', $resolution->source);
    }

    public function test_insufficient_confidence_remains_a_typed_clarification_without_domain_guessing(): void
    {
        $clarification = new ClarificationSet('en', [[
            'code' => 'content_goal',
            'question' => 'Which content is your priority?',
        ]]);
        $resolution = (new ObtainBuildIntent(new IntentOutcomeGateway(
            new AiGatewayOutcome('fallback', $clarification),
        )))->handle($this->request());

        self::assertNull($resolution->intent);
        self::assertSame($clarification, $resolution->clarifications);
        self::assertSame('fallback', $resolution->source);
    }

    private function request(): NaturalLanguageIntentRequest
    {
        return new NaturalLanguageIntentRequest(
            'Improve the fixture build.',
            DomainFixtures::value(Locale::from('en-US'), Locale::class),
            new IntentVocabulary(
                GameEdition::Poe1,
                '1.2.3',
                '1.0.0',
                str_repeat('b', 64),
                ['fixture.content'],
                ['fixture.style'],
                ['fixture.constraint'],
            ),
            new AiRequestContext(str_repeat('a', 64), str_repeat('b', 64), true),
        );
    }
}

final readonly class IntentOutcomeGateway implements AiGateway
{
    public function __construct(private AiGatewayOutcome $outcome) {}

    public function extractIntent(NaturalLanguageIntentRequest $request): AiGatewayOutcome
    {
        return $this->outcome;
    }

    public function explain(GatewayExplanationRequest $request): AiGatewayOutcome
    {
        return $this->outcome;
    }
}
