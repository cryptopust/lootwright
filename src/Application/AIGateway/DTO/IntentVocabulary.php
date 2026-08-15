<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class IntentVocabulary
{
    /**
     * @param  list<string>  $contentGoals
     * @param  list<string>  $playStyles
     * @param  list<string>  $constraintCodes
     */
    public function __construct(
        public GameEdition $edition,
        public string $patch,
        public string $rulesetVersion,
        public string $rulesetChecksum,
        public array $contentGoals,
        public array $playStyles,
        public array $constraintCodes,
    ) {
        if ($patch === '' || $rulesetVersion === '' || preg_match('/^[a-f0-9]{64}$/D', $rulesetChecksum) !== 1) {
            throw new InvalidArgumentException('Intent vocabulary requires an exact patch and checksum-bound ruleset.');
        }

        foreach ([$contentGoals, $playStyles, $constraintCodes] as $values) {
            if ($values === [] || count($values) !== count(array_unique($values))) {
                throw new InvalidArgumentException('Intent vocabulary lists must be non-empty and unique.');
            }

            foreach ($values as $value) {
                if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $value) !== 1) {
                    throw new InvalidArgumentException('Intent vocabulary contains a non-canonical term.');
                }
            }
        }
    }
}
