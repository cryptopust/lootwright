<?php

namespace Lootwright\Domain\PoeCatalog\Item;

use JsonSerializable;
use Lootwright\Domain\PoeCatalog\Identifier\AffixId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemBaseId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;
use Lootwright\Domain\PoeCatalog\Identifier\ModifierId;
use Lootwright\Domain\PoeCatalog\Identifier\StatId;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\EditionScopedValue;

final readonly class CanonicalItem implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $modifiers
     * @param  array<array-key, mixed>  $stats
     * @param  array<array-key, mixed>  $affixes
     */
    private function __construct(
        public GameEdition $edition,
        public ItemId $id,
        public ItemBaseId $baseId,
        public ItemSlotId $slotId,
        public ItemRarity $rarity,
        public array $modifiers,
        public array $stats,
        public array $affixes,
    ) {}

    /**
     * @param  array<array-key, mixed>  $modifiers
     * @param  array<array-key, mixed>  $stats
     * @param  array<array-key, mixed>  $affixes
     */
    public static function create(
        GameEdition $edition,
        ItemId $id,
        ItemBaseId $baseId,
        ItemSlotId $slotId,
        ItemRarity $rarity,
        array $modifiers = [],
        array $stats = [],
        array $affixes = [],
    ): DomainResult {
        foreach ([$id, $baseId, $slotId] as $value) {
            if (! $value->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every canonical item identifier must belong to the item edition.',
                ));
            }
        }

        $validatedModifiers = [];

        foreach ($modifiers as $modifier) {
            if (! $modifier instanceof ModifierId) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Every item modifier must be a ModifierId.',
                ));
            }

            if (! $modifier->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every item modifier must belong to the item edition.',
                ));
            }

            $validatedModifiers[] = $modifier;
        }

        $validatedStats = [];

        foreach ($stats as $stat) {
            if (! $stat instanceof StatId) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Every item stat must be a StatId.',
                ));
            }

            if (! $stat->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every item stat must belong to the item edition.',
                ));
            }

            $validatedStats[] = $stat;
        }

        $validatedAffixes = [];

        foreach ($affixes as $affix) {
            if (! $affix instanceof AffixId) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Every item affix must be an AffixId.',
                ));
            }

            if (! $affix->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every item affix must belong to the item edition.',
                ));
            }

            $validatedAffixes[] = $affix;
        }

        foreach ([$validatedModifiers, $validatedStats, $validatedAffixes] as $identifiers) {
            $keys = array_map(
                static fn (EditionScopedValue $identifier): string => $identifier::class.':'.$identifier->value,
                $identifiers,
            );

            if (count($keys) !== count(array_unique($keys))) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::DuplicateValue,
                    'Canonical item identifier collections cannot contain duplicates.',
                ));
            }
        }

        return DomainResult::success(new self(
            $edition,
            $id,
            $baseId,
            $slotId,
            $rarity,
            $validatedModifiers,
            $validatedStats,
            $validatedAffixes,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'id' => $this->id,
            'base_id' => $this->baseId,
            'slot_id' => $this->slotId,
            'rarity' => $this->rarity->value,
            'modifiers' => $this->modifiers,
            'stats' => $this->stats,
            'affixes' => $this->affixes,
        ];
    }
}
