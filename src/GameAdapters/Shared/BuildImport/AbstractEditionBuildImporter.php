<?php

namespace Lootwright\GameAdapters\Shared\BuildImport;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\BuildIntake\Ports\BuildImporter;
use Lootwright\Domain\BuildIntake\Ports\ItemTextBuildImporter;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;

abstract readonly class AbstractEditionBuildImporter implements BuildImporter
{
    public function __construct(
        private PobImportCoordinator $pobImporter,
        private ItemTextBuildImporter $itemTextImporter,
    ) {}

    abstract public function edition(): GameEdition;

    public function supports(BuildInputType $inputType): bool
    {
        return in_array($inputType, BuildInputType::cases(), true);
    }

    public function import(string $input, BuildInputType $inputType, ImportLimits $limits): DomainResult
    {
        $result = $inputType === BuildInputType::ItemText
            ? $this->itemTextImporter->import($input, $limits)
            : $this->pobImporter->import($input, $limits);

        if ($result->isFailure()) {
            return $result;
        }

        $value = $result->value();

        if (! $value instanceof PobImportResult) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::UnsupportedInput,
                'The build adapter returned an invalid normalized result.',
            ));
        }

        if ($value->canonicalBuild->edition !== $this->edition()) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'The detected build edition differs from the selected build adapter.',
            ));
        }

        if ($value->canonicalBuild->sourceMetadata?->inputType !== $inputType) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::UnsupportedInput,
                'The detected build format differs from the requested input type.',
            ));
        }

        return $result;
    }
}
