<?php

namespace Lootwright\Application\BuildIntake;

use DOMDocument;
use DOMElement;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Ports\PobBuildParser;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\GameAdapters\Shared\Pob\DecodedPobInput;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
use Lootwright\GameAdapters\Shared\Pob\SafeXmlParser;

final readonly class PobImportService
{
    /** @param list<PobBuildParser> $parsers */
    public function __construct(
        private PobEnvelopeDecoder $decoder,
        private SafeXmlParser $xmlParser,
        private array $parsers,
    ) {}

    public function import(string $input, ?ImportLimits $limits = null): DomainResult
    {
        $limits ??= new ImportLimits;
        $preparedResult = $this->prepare($input, $limits);

        if ($preparedResult->isFailure()) {
            return $preparedResult;
        }

        $prepared = $preparedResult->value();

        if (! $prepared instanceof PreparedPobInput) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The prepared build input is invalid.');
        }

        return $this->normalize($prepared, $limits);
    }

    public function prepare(string $input, ?ImportLimits $limits = null): DomainResult
    {
        $limits ??= new ImportLimits;
        $decodedResult = $this->decoder->decode($input, $limits);

        if ($decodedResult->isFailure()) {
            return $decodedResult;
        }

        $decoded = $decodedResult->value();

        if (! $decoded instanceof DecodedPobInput) {
            return $this->failure(DomainErrorCode::InvalidEncoding, 'The decoded build envelope is invalid.');
        }

        $documentResult = $this->xmlParser->parse($decoded->xml, $limits);

        if ($documentResult->isFailure()) {
            return $documentResult;
        }

        $document = $documentResult->value();

        if (! $document instanceof DOMDocument || ! $document->documentElement instanceof DOMElement) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The parsed build document is invalid.');
        }

        $root = $document->documentElement->tagName;
        $oppositeMarker = $root === 'PathOfBuilding' ? 'PathOfBuilding2' : 'PathOfBuilding';

        if ($document->getElementsByTagName($oppositeMarker)->length > 0) {
            return $this->failure(DomainErrorCode::AmbiguousGameEdition, 'The build contains conflicting PoE1 and PoE2 structural markers.');
        }

        if (! in_array($root, ['PathOfBuilding', 'PathOfBuilding2'], true)) {
            return $this->failure(DomainErrorCode::AmbiguousGameEdition, 'The build edition cannot be proven from its root element.');
        }

        return DomainResult::success(new PreparedPobInput($document, $root, $decoded->checksumSha256));
    }

    public function normalize(PreparedPobInput $prepared, ?ImportLimits $limits = null): DomainResult
    {
        $limits ??= new ImportLimits;

        foreach ($this->parsers as $parser) {
            if ($parser->rootElement() === $prepared->rootElement) {
                return $parser->parse($prepared->document, $prepared->checksumSha256, $limits);
            }
        }

        return $this->failure(DomainErrorCode::AmbiguousGameEdition, 'The build edition cannot be proven from its root element.');
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }
}
