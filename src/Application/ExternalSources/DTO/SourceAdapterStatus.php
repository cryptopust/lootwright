<?php

namespace Lootwright\Application\ExternalSources\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class SourceAdapterStatus implements JsonSerializable
{
    /**
     * @param  list<GameEdition>  $editions
     * @param  list<string>  $capabilities
     */
    public function __construct(
        public string $sourceCode,
        public string $sourceVersion,
        public array $editions,
        public array $capabilities,
        public bool $operational,
        public ?string $disabledReason,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'source_code' => $this->sourceCode,
            'source_version' => $this->sourceVersion,
            'editions' => array_map(static fn (GameEdition $edition): string => $edition->value, $this->editions),
            'capabilities' => $this->capabilities,
            'operational' => $this->operational,
            'disabled_reason' => $this->disabledReason,
        ];
    }
}
