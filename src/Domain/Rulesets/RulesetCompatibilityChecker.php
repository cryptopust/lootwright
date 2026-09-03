<?php

namespace Lootwright\Domain\Rulesets;

use Lootwright\Domain\Shared\Game\GameEdition;

final class RulesetCompatibilityChecker
{
    public function check(
        GameEdition $edition,
        string $patch,
        ?string $league,
        string $parserVersion,
        GameRuleset $ruleset,
    ): RulesetCompatibilityStatus {
        if (! $ruleset->approvedForProduction()) {
            if ($ruleset->compatibilityStatus !== RulesetCompatibilityStatus::Compatible) {
                return $ruleset->compatibilityStatus;
            }

            return match ($ruleset->classification) {
                DatasetClassification::Fixture => RulesetCompatibilityStatus::FixtureRejected,
                DatasetClassification::Unavailable => RulesetCompatibilityStatus::Unavailable,
                default => RulesetCompatibilityStatus::InvalidProvenance,
            };
        }

        if ($ruleset->identity->edition !== $edition) {
            return RulesetCompatibilityStatus::Unavailable;
        }
        if ($ruleset->identity->patch->value !== $patch
            || $ruleset->identity->league?->value !== $league
        ) {
            return RulesetCompatibilityStatus::UnsupportedPatch;
        }
        if ($ruleset->identity->parserVersion->value !== $parserVersion) {
            return RulesetCompatibilityStatus::IncompatibleParser;
        }

        return RulesetCompatibilityStatus::Compatible;
    }
}
