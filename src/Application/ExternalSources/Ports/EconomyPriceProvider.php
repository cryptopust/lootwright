<?php
namespace Lootwright\Application\ExternalSources\Ports;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;
use Lootwright\Application\ExternalSources\DTO\EconomyQuote;
interface EconomyPriceProvider { /** @return list<EconomyQuote> */ public function quotes(string $league, EconomyCategory $category): array; }
