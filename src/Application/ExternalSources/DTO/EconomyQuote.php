<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class EconomyQuote { public function __construct(public PriceEvidence $evidence, public array $confidenceMetadata = []) {} }
