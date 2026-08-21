<?php

namespace Lootwright\GameAdapters\Shared\Pob;

use DOMDocument;
use DOMElement;
use DOMNode;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final class SafeXmlParser
{
    public function parse(string $xml, ImportLimits $limits): DomainResult
    {
        if (! mb_check_encoding($xml, 'UTF-8')) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The XML build is not valid UTF-8.');
        }

        if (preg_match('/^\s*<\?xml[^>]*\bencoding\s*=\s*["\']([^"\']+)["\']/i', $xml, $encoding) === 1
            && strtoupper(str_replace('_', '-', $encoding[1])) !== 'UTF-8'
        ) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The XML declaration must use UTF-8.');
        }

        if (preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $xml) === 1) {
            return $this->failure(DomainErrorCode::UnsafeXml, 'DTD and entity declarations are not permitted.');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new DOMDocument;
        $document->resolveExternals = false;
        $document->substituteEntities = false;
        $document->validateOnParse = false;

        try {
            $loaded = $document->loadXML(
                $xml,
                LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_BIGLINES,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded || ! $document->documentElement instanceof DOMElement) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The XML build is malformed.');
        }

        $stack = [[$document->documentElement, 1]];
        $elements = 0;
        $textBytes = 0;

        while ($stack !== []) {
            [$node, $depth] = array_pop($stack);

            if (! $node instanceof DOMNode || ! is_int($depth)) {
                return $this->failure(DomainErrorCode::InvalidXml, 'The XML node structure is invalid.');
            }

            if ($depth > $limits->xmlDepth) {
                return $this->failure(DomainErrorCode::InputTooLarge, 'The XML build exceeds the nesting-depth limit.');
            }

            if ($node instanceof DOMElement) {
                $elements++;

                if ($elements > $limits->xmlElements || $node->attributes->length > $limits->attributesPerElement) {
                    return $this->failure(DomainErrorCode::InputTooLarge, 'The XML build exceeds structural limits.');
                }

                foreach ($node->attributes as $attribute) {
                    if (strlen($attribute->nodeName) > $limits->attributeBytes
                        || strlen($attribute->nodeValue ?? '') > $limits->attributeBytes
                    ) {
                        return $this->failure(DomainErrorCode::InputTooLarge, 'The XML build contains an oversized attribute.');
                    }
                }
            } elseif (in_array($node->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE, XML_COMMENT_NODE], true)) {
                $nodeBytes = strlen($node->nodeValue ?? '');
                $textBytes += $nodeBytes;

                if ($nodeBytes > $limits->textBytes || $textBytes > $limits->xmlTextBytes) {
                    return $this->failure(DomainErrorCode::InputTooLarge, 'The XML build exceeds the text-content limit.');
                }
            }

            foreach ($node->childNodes as $child) {
                $stack[] = [$child, $child instanceof DOMElement ? $depth + 1 : $depth];
            }
        }

        return DomainResult::success($document);
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }
}
