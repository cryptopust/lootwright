<?php

namespace Lootwright\GameAdapters\PoE2\Pob;

use DOMDocument;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Ports\PobBuildParser;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class Pob2Parser implements PobBuildParser
{
    public function __construct(private Pob2Normalizer $normalizer) {}

    public function rootElement(): string
    {
        return 'PathOfBuilding2';
    }

    public function parse(DOMDocument $document, string $inputChecksum, ImportLimits $limits): DomainResult
    {
        if ($document->documentElement?->tagName !== $this->rootElement()) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::AmbiguousGameEdition, 'The document is not a PoE2 PoB build.'));
        }

        return $this->normalizer->normalize($document, $inputChecksum, $limits);
    }
}
