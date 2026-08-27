<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use Lootwright\Domain\Analysis\RuleRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\RulesetVersion;

/** Edition-scoped PoE2 rule catalogue. It never imports PoE1 rules. */
final readonly class Poe2RuleRegistry implements RuleRegistry
{
    public function __construct(private RulesetVersion $rulesetVersion)
    {
        if (! $rulesetVersion->belongsTo(GameEdition::Poe2)) {
            throw new \InvalidArgumentException('A PoE2 registry requires a PoE2 ruleset version.');
        }
    }

    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    public function version(): RulesetVersion
    {
        return $this->rulesetVersion;
    }

    public function rules(): array
    {
        $configuration = Poe2AnalysisRuleset::publishedV1();
        $rules = [];
        foreach ($configuration->ruleCodes as $ruleId) {
            $rules[] = new Poe2RegisteredRule($ruleId);
        }

        return $rules;
    }
}
