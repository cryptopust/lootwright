<?php

namespace Lootwright\GameAdapters\Shared\BuildImport;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Ports\BuildImporter;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class BuildImportCoordinator
{
    /** @param list<BuildImporter> $importers */
    public function __construct(private array $importers) {}

    public function import(
        string $input,
        BuildInputType $inputType,
        GameEdition $expectedEdition,
        ?ImportLimits $limits = null,
    ): DomainResult {
        foreach ($this->importers as $importer) {
            if ($importer->edition() === $expectedEdition && $importer->supports($inputType)) {
                return $importer->import($input, $inputType, $limits ?? new ImportLimits);
            }
        }

        return DomainResult::failure(DomainError::because(
            DomainErrorCode::UnsupportedInput,
            'No approved build adapter supports this edition and input type.',
        ));
    }

    public function importDetected(
        string $input,
        GameEdition $expectedEdition,
        ?ImportLimits $limits = null,
    ): DomainResult {
        return $this->import($input, $this->detect($input), $expectedEdition, $limits);
    }

    public function detect(string $input): BuildInputType
    {
        $trimmed = ltrim($input);

        if (str_starts_with($trimmed, '<')) {
            return BuildInputType::DecodedXml;
        }

        if (preg_match('/\A(?:Item Class|Rarity):/D', $trimmed) === 1) {
            return BuildInputType::ItemText;
        }

        return BuildInputType::PobShareCode;
    }
}
