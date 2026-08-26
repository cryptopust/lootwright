<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

use JsonSerializable;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use RuntimeException;

final readonly class Poe1AnalysisRuleset implements JsonSerializable
{
    /**
     * @param  array<string, list<string>>  $playerStatAliases
     * @param  array<string, list<string>>  $requiredSlotAliases
     * @param  list<string>  $ruleCodes
     * @param  array<string, array<string, int>>  $contentProfiles
     */
    private function __construct(
        public string $engineVersion,
        public array $playerStatAliases,
        public array $requiredSlotAliases,
        public int $minimumMainSkillLinks,
        public array $ruleCodes,
        public array $contentProfiles,
    ) {}

    public static function publishedV1(): self
    {
        return new self(
            '1.0.0',
            Poe1PlayerStatAliasRegistry::DEFAULT_ALIASES,
            [
                'body_armour' => ['body armour', 'bodyarmor'],
                'boots' => ['boots'],
                'gloves' => ['gloves'],
                'helmet' => ['helmet', 'helm'],
            ],
            4,
            Poe1DeterministicAnalysisEngine::RULE_CODES,
            [
                'mapping' => ['life' => 3500, 'energy_shield' => 0, 'elemental_resistance' => 75, 'suppression' => 0],
                'bossing' => ['life' => 4500, 'energy_shield' => 0, 'elemental_resistance' => 75, 'suppression' => 0],
                'delve' => ['life' => 5000, 'energy_shield' => 0, 'elemental_resistance' => 75, 'suppression' => 0],
                'simulacrum' => ['life' => 5000, 'energy_shield' => 0, 'elemental_resistance' => 75, 'suppression' => 0],
                'sanctum' => ['life' => 3500, 'energy_shield' => 0, 'elemental_resistance' => 75, 'suppression' => 0],
                'progression' => ['life' => 2500, 'energy_shield' => 0, 'elemental_resistance' => 75, 'suppression' => 0],
            ],
        );
    }

    /** @param array<string, mixed> $payload */
    public static function fromPublishedPayload(array $payload): self
    {
        $published = self::publishedV1();
        if (! hash_equals(CanonicalJson::encode($published), CanonicalJson::encode($payload))) {
            throw new RuntimeException('The deterministic analysis manifest is not a reviewed published version.');
        }

        return $published;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'engine_version' => $this->engineVersion,
            'minimum_main_skill_links' => $this->minimumMainSkillLinks,
            'player_stat_aliases' => $this->playerStatAliases,
            'required_slot_aliases' => $this->requiredSlotAliases,
            'rule_codes' => $this->ruleCodes,
            'content_profiles' => $this->contentProfiles,
        ];
    }
}
