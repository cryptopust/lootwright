<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lootwright\Application\AIGateway\DTO\AiRequestContext;
use Lootwright\Application\AIGateway\DTO\IntentVocabulary;
use Lootwright\Application\AIGateway\DTO\StructuredAiRequest;
use Lootwright\Application\AIGateway\Ports\AiBudget;
use Lootwright\Application\AIGateway\Ports\AiExecutionPolicy;
use Lootwright\Application\AIGateway\Ports\StructuredAiProvider;
use Lootwright\Application\AIGateway\Schema\StrictJsonSchemaValidator;
use Lootwright\Application\AIGateway\Schema\StructuredSchemas;
use Lootwright\Domain\Shared\Game\GameEdition;

final class SmokeTestOpenAi extends Command
{
    protected $signature = 'ai:smoke-openai {--confirm : Explicitly authorize one synthetic request} {--max-cost-micro-usd= : Hard cap for this smoke request}';

    protected $description = 'Run one explicit, synthetic, budget-capped OpenAI Responses API smoke test';

    public function handle(
        StructuredAiProvider $provider,
        AiExecutionPolicy $policy,
        AiBudget $budget,
        StrictJsonSchemaValidator $validator,
    ): int {
        $cap = filter_var($this->option('max-cost-micro-usd'), FILTER_VALIDATE_INT);

        if (! $this->option('confirm') || ! is_int($cap) || $cap < 1) {
            $this->error('Refused: pass --confirm and a positive --max-cost-micro-usd cap.');

            return self::FAILURE;
        }
        if (! config('ai.enabled') || trim((string) config('ai.api_key')) === '') {
            $this->error('Refused: OpenAI is disabled or its secret is not configured.');

            return self::FAILURE;
        }
        if (! $policy->permits('intent')) {
            $this->error('Refused: Policy Gate does not allow the exact OpenAI intent operation.');

            return self::FAILURE;
        }

        $configuredMaximum = (int) ceil((((int) config('ai.max_input_tokens') * 200_000)
            + ((int) config('ai.intent_max_output_tokens') * 1_250_000)) / 1_000_000);
        if ($configuredMaximum > $cap) {
            $this->error("Refused: configured worst-case cost {$configuredMaximum} micro-USD exceeds the supplied cap.");

            return self::FAILURE;
        }

        $context = new AiRequestContext(hash('sha256', 'manual-smoke-user'), hash('sha256', 'manual-smoke-ip'), true, false);
        $reservation = $budget->reserve($context, $configuredMaximum);
        if ($reservation === null) {
            $this->error('Refused: a local AI budget or monthly circuit breaker is exhausted.');

            return self::FAILURE;
        }

        $vocabulary = new IntentVocabulary(GameEdition::Poe1, 'smoke', 'smoke-1', str_repeat('0', 64), ['mapping'], ['balanced'], ['budget']);
        $schema = StructuredSchemas::buildIntent($vocabulary);

        try {
            $response = $provider->respond(new StructuredAiRequest(
                'intent',
                (string) config('ai.intent_model'),
                (string) config('ai.prompt_template_version'),
                'Synthetic smoke test. Select only supplied values.',
                ['description' => 'mapping balanced', 'approved_terms' => ['mapping', 'balanced', 'budget']],
                'build_intent_smoke',
                $schema,
                (int) config('ai.intent_max_output_tokens'),
                substr($context->userHash, 0, 64),
                hash('sha256', 'lootwright-openai-smoke'),
            ));
        } catch (\Throwable) {
            $budget->cancel($reservation);
            $this->error('Smoke request failed; no response content or secret was printed.');

            return self::FAILURE;
        }

        $actual = (int) ceil((($response->inputTokens * 200_000) + ($response->outputTokens * 1_250_000)) / 1_000_000);
        $budget->settle($reservation, $actual);
        $decoded = is_string($response->outputJson) ? json_decode($response->outputJson, true) : null;
        $valid = is_array($decoded) && $validator->validate($decoded, $schema) === [] && ! $response->refused;

        $this->line('Provider: openai');
        $this->line('Model: '.$response->model);
        $this->line('Schema valid: '.($valid ? 'yes' : 'no'));
        $this->line('Token usage: input='.$response->inputTokens.', output='.$response->outputTokens);
        $this->line('Recorded cost: '.$actual.' micro-USD');

        return $valid ? self::SUCCESS : self::FAILURE;
    }
}
