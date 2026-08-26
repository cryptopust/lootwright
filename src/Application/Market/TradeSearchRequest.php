<?php

namespace Lootwright\Application\Market;

use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class TradeSearchRequest
{
    /** @param array<string,mixed> $filters */
    public function __construct(
        public GameEdition $edition,
        public string $league,
        public array $filters,
    ) {}
}
