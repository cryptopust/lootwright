<?php

namespace Lootwright\Domain\PoeCatalog;

use JsonSerializable;
use Lootwright\Domain\PoeCatalog\Character\CharacterCatalogRegistry;
use Lootwright\Domain\PoeCatalog\Identifier\AscendancyId;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\PoeCatalog\Identifier\KeystoneId;
use Lootwright\Domain\PoeCatalog\Identifier\PassiveNodeId;
use Lootwright\Domain\PoeCatalog\Identifier\SkillId;
use Lootwright\Domain\PoeCatalog\Identifier\SupportGemId;
use Lootwright\Domain\PoeCatalog\Item\CanonicalItem;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\EditionScopedValue;

final readonly class BuildCatalog implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $skills
     * @param  array<array-key, mixed>  $supportGems
     * @param  array<array-key, mixed>  $passiveNodes
     * @param  array<array-key, mixed>  $keystones
     * @param  array<array-key, mixed>  $items
     */
    private function __construct(
        public GameEdition $edition,
        public CharacterClassId $characterClass,
        public ?AscendancyId $ascendancy,
        public array $skills,
        public array $supportGems,
        public array $passiveNodes,
        public array $keystones,
        public array $items,
    ) {}

    /**
     * @param  array<array-key, mixed>  $skills
     * @param  array<array-key, mixed>  $supportGems
     * @param  array<array-key, mixed>  $passiveNodes
     * @param  array<array-key, mixed>  $keystones
     * @param  array<array-key, mixed>  $items
     */
    public static function create(
        GameEdition $edition,
        CharacterClassId $characterClass,
        ?AscendancyId $ascendancy,
        array $skills = [],
        array $supportGems = [],
        array $passiveNodes = [],
        array $keystones = [],
        array $items = [],
    ): DomainResult {
        if (! $characterClass->belongsTo($edition)
            || ($ascendancy !== null && ! $ascendancy->belongsTo($edition))
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'The character class and ascendancy must belong to the build edition.',
            ));
        }

        if (! CharacterCatalogRegistry::for($edition)->supports($characterClass->value, $ascendancy?->value)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'The selected Ascendancy does not belong to the selected character class and game.',
            ));
        }

        $validatedSkills = [];

        foreach ($skills as $skill) {
            if (! $skill instanceof SkillId || ! $skill->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every skill must have the expected type and build edition.',
                ));
            }

            $validatedSkills[] = $skill;
        }

        $validatedSupportGems = [];

        foreach ($supportGems as $supportGem) {
            if (! $supportGem instanceof SupportGemId || ! $supportGem->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every support gem must have the expected type and build edition.',
                ));
            }

            $validatedSupportGems[] = $supportGem;
        }

        $validatedPassiveNodes = [];

        foreach ($passiveNodes as $passiveNode) {
            if (! $passiveNode instanceof PassiveNodeId || ! $passiveNode->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every passive node must have the expected type and build edition.',
                ));
            }

            $validatedPassiveNodes[] = $passiveNode;
        }

        $validatedKeystones = [];

        foreach ($keystones as $keystone) {
            if (! $keystone instanceof KeystoneId || ! $keystone->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every keystone must have the expected type and build edition.',
                ));
            }

            $validatedKeystones[] = $keystone;
        }

        $validatedItems = [];

        foreach ($items as $item) {
            if (! $item instanceof CanonicalItem || $item->edition !== $edition) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every canonical item must belong to the build edition.',
                ));
            }

            $validatedItems[] = $item;
        }

        $identifiers = [
            $characterClass,
            ...($ascendancy === null ? [] : [$ascendancy]),
            ...$validatedSkills,
            ...$validatedSupportGems,
            ...$validatedPassiveNodes,
            ...$validatedKeystones,
        ];
        $keys = array_map(
            static fn (EditionScopedValue $identifier): string => $identifier::class.':'.$identifier->value,
            $identifiers,
        );

        if (count($keys) !== count(array_unique($keys))) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::DuplicateValue,
                'Build catalog identifier collections cannot contain duplicates.',
            ));
        }

        $itemKeys = array_map(
            static fn (CanonicalItem $item): string => $item->id->value,
            $validatedItems,
        );

        if (count($itemKeys) !== count(array_unique($itemKeys))) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::DuplicateValue,
                'A build cannot contain the same canonical item twice.',
            ));
        }

        return DomainResult::success(new self(
            $edition,
            $characterClass,
            $ascendancy,
            $validatedSkills,
            $validatedSupportGems,
            $validatedPassiveNodes,
            $validatedKeystones,
            $validatedItems,
        ));
    }

    /** Construct a build shell from an already-resolved immutable ruleset. */
    public static function fromCanonical(
        GameEdition $edition,
        CharacterClassId $characterClass,
        ?AscendancyId $ascendancy,
    ): DomainResult {
        if (! $characterClass->belongsTo($edition)
            || ($ascendancy !== null && ! $ascendancy->belongsTo($edition))) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'Canonical build values must belong to the selected edition.',
            ));
        }

        return DomainResult::success(new self($edition, $characterClass, $ascendancy, [], [], [], [], []));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'character_class' => $this->characterClass,
            'ascendancy' => $this->ascendancy,
            'skills' => $this->skills,
            'support_gems' => $this->supportGems,
            'passive_nodes' => $this->passiveNodes,
            'keystones' => $this->keystones,
            'items' => $this->items,
        ];
    }
}
