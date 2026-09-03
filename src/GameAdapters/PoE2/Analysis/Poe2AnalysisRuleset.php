<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use JsonSerializable;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use RuntimeException;

/** Independently versioned PoE2 analysis manifest. It contains no PoE1 rules. */
final readonly class Poe2AnalysisRuleset implements JsonSerializable
{
    /** @param list<string> $ruleCodes
     * @param  array<string, list<string>>  $metricAliases
     * @param  array<string, array<string, int>>  $contentProfiles
     */
    private function __construct(
        public string $engineVersion,
        public array $ruleCodes,
        public array $metricAliases,
        /** @var array<string, array<string, int>> */ public array $contentProfiles,
    ) {}

    public static function publishedV1(): self
    {
        return new self('1.0.0', [
            'poe2.data.character.level.missing',
            'poe2.data.character.class.missing',
            'poe2.data.character.ascendancy.missing',
            'poe2.skills.main.missing',
            'poe2.data.resistances.unavailable',
        ], [
            'life' => ['Life', 'life'],
            'energy_shield' => ['EnergyShield', 'energy_shield'],
            'mana' => ['Mana', 'mana'],
            'armour' => ['Armour', 'armour'],
            'evasion' => ['Evasion', 'evasion'],
        ], [
            'progression' => ['life' => 0],
            'mapping' => ['life' => 0],
            'bossing' => ['life' => 0],
        ]);
    }

    /** @param array<string,mixed> $payload */
    public static function fromPublishedPayload(array $payload): self
    {
        $published = self::publishedV1();
        if (! hash_equals(CanonicalJson::encode($published), CanonicalJson::encode($payload))) {
            throw new RuntimeException('The deterministic PoE2 analysis manifest is not approved.');
        }

        return $published;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'engine_version' => $this->engineVersion,
            'rule_codes' => $this->ruleCodes,
            'metric_aliases' => $this->metricAliases,
            'content_profiles' => $this->contentProfiles,
        ];
    }
}
