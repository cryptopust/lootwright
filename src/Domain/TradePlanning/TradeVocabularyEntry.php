<?php

namespace Lootwright\Domain\TradePlanning;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;

/** A ruleset-owned, edition-scoped human Trade vocabulary entry. */
final readonly class TradeVocabularyEntry implements JsonSerializable
{
    /** @param list<string> $conflicts */
    public function __construct(
        public GameEdition $edition,
        public string $canonicalModifierId,
        public string $label,
        public SourceProvenanceReference $provenance,
        public ?string $documentedIdentifier = null,
        public array $conflicts = [],
    ) {
        if ($provenance->edition !== $edition
            || preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $canonicalModifierId) !== 1
            || trim($label) === '' || mb_strlen($label) > 240
            || preg_match('#(?:https?://|/api/|[{}]|[\x00-\x08\x0B\x0C\x0E-\x1F])#i', $label) === 1
            || ($documentedIdentifier !== null && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $documentedIdentifier) !== 1)
        ) {
            throw new InvalidArgumentException('Trade vocabulary entries require bounded edition-scoped identity and provenance.');
        }

        foreach ($conflicts as $conflict) {
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $conflict) !== 1) {
                throw new InvalidArgumentException('Trade vocabulary conflicts must be canonical identifiers.');
            }
        }
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'canonical_modifier_id' => $this->canonicalModifierId,
            'label' => $this->label,
            'documented_identifier' => $this->documentedIdentifier,
            'conflicts' => $this->conflicts,
            'provenance' => $this->provenance,
        ];
    }
}
