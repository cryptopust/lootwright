<?php

namespace Lootwright\Application\Market;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

/** A validated, provider-neutral search recipe. It deliberately has no Trade IDs or URL. */
final readonly class TradeSearchPlan implements JsonSerializable
{
    /** @param list<array<string,mixed>> $filters */
    public function __construct(
        public GameEdition $edition,
        public string $league,
        public TradeSearchMode $mode,
        public array $filters,
        public string $copyText,
        public ?string $officialTradeUrl = null,
    ) {
        if (trim($league) === '' || trim($copyText) === '' || ($officialTradeUrl !== null && $officialTradeUrl !== 'https://www.pathofexile.com/trade')) {
            throw new InvalidArgumentException('Trade search plans require edition, league and a manual validated recipe.');
        }
        foreach ($filters as $filter) {
            if (! is_string($filter['canonical_modifier_id'] ?? null)
                || preg_match('/^[a-z][a-z0-9._:-]{1,127}$/D', $filter['canonical_modifier_id']) !== 1
                || isset($filter['trade_stat_id'])) {
                throw new InvalidArgumentException('Trade search filters may contain only Lootwright canonical modifier IDs.');
            }
        }
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return ['edition' => $this->edition->value, 'league' => $this->league, 'mode' => $this->mode->value, 'filters' => $this->filters, 'copy_text' => $this->copyText, 'official_trade_url' => $this->officialTradeUrl];
    }
}
