<?php

namespace Lootwright\GameAdapters\PoE2\Pob;

use Lootwright\Domain\BuildIntake\Import\ImportProvenance;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\Pob\AbstractPobNormalizer;

final class Pob2Normalizer extends AbstractPobNormalizer
{
    public const SOURCE_COMMIT = '5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6';

    public const LICENSE_SHA256 = '22d2d075c1d361971764fbbd1e12e1485bdf35f0769ffac4eca8a79afc60dda8';

    public const PARSER_VERSION = '1.0.0-beta.1';

    protected function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    protected function beta(): bool
    {
        return true;
    }

    protected function provenance(): ImportProvenance
    {
        return new ImportProvenance('POB2-FORMAT-001', self::SOURCE_COMMIT, self::LICENSE_SHA256, self::PARSER_VERSION);
    }

    protected function choiceAttributes(): array
    {
        return [];
    }
}
