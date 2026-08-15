<?php

namespace Lootwright\Application\AIGateway\Services;

use Lootwright\Application\AIGateway\DTO\BuildIntentCandidate;
use Lootwright\Application\AIGateway\DTO\BuildIntentResolution;
use Lootwright\Application\AIGateway\DTO\ClarificationSet;
use Lootwright\Application\AIGateway\DTO\NaturalLanguageIntentRequest;
use Lootwright\Application\AIGateway\Ports\AiGateway;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\Constraint;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayerGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayStyle;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Value\Confidence;
use RuntimeException;

final readonly class ObtainBuildIntent
{
    public function __construct(private AiGateway $gateway) {}

    public function handle(NaturalLanguageIntentRequest $request): BuildIntentResolution
    {
        $outcome = $this->gateway->extractIntent($request);

        if ($outcome->value instanceof ClarificationSet) {
            return new BuildIntentResolution(null, $outcome->value, $outcome->status);
        }
        if (! $outcome->value instanceof BuildIntentCandidate) {
            throw new RuntimeException('Intent extraction returned an incompatible result.');
        }

        $candidate = $outcome->value;
        $constraints = [];

        foreach ($candidate->constraints as $constraint) {
            $priority = match ($constraint['priority']) {
                'critical' => UpgradePriority::Critical,
                'high' => UpgradePriority::High,
                'medium' => UpgradePriority::Medium,
                'low' => UpgradePriority::Low,
                default => throw new RuntimeException('A schema-valid AI candidate contained an unknown priority.'),
            };
            $constraints[] = $this->value(
                Constraint::create($constraint['code'], $constraint['value'], $priority),
                Constraint::class,
            );
        }

        $goal = $this->value(PlayerGoal::create(
            $candidate->edition,
            $request->description,
            $this->value(ContentGoal::from($candidate->edition, $candidate->contentGoal), ContentGoal::class),
            $this->value(PlayStyle::from($candidate->edition, $candidate->playStyle), PlayStyle::class),
            $constraints,
        ), PlayerGoal::class);
        $intent = $this->value(BuildIntent::create(
            $goal,
            $request->locale,
            $this->value(Confidence::fromBasisPoints($candidate->confidenceBasisPoints), Confidence::class),
            [],
        ), BuildIntent::class);

        return new BuildIntentResolution($intent, null, $outcome->status);
    }

    /** @template TObject of object
     * @param  class-string<TObject>  $expected
     * @return TObject
     */
    private function value(DomainResult $result, string $expected): object
    {
        if ($result->isFailure() || ! $result->value() instanceof $expected) {
            throw new RuntimeException('A schema-valid AI candidate failed deterministic domain resolution.');
        }

        return $result->value();
    }
}
