<?php

namespace Lootwright\GameAdapters\PoE1\Pob;

use Lootwright\Domain\BuildIntake\Import\ImportProvenance;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\Pob\AbstractPobNormalizer;

final class Pob1Normalizer extends AbstractPobNormalizer
{
    public const SOURCE_COMMIT = 'bcbca9b60b04abc17935c84ff3589342193bd758';

    public const LICENSE_SHA256 = 'd5e0e888aaf923e4a1e85078f2ae24602baa79d883a359c3ed928354a57bd0db';

    public const PARSER_VERSION = '1.0.0';

    protected function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    protected function beta(): bool
    {
        return false;
    }

    protected function provenance(): ImportProvenance
    {
        return new ImportProvenance('POB1-FORMAT-001', self::SOURCE_COMMIT, self::LICENSE_SHA256, self::PARSER_VERSION);
    }

    protected function choiceAttributes(): array
    {
        return ['bandit', 'pantheonMajorGod', 'pantheonMinorGod'];
    }
}
