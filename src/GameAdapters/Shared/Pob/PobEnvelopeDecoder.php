<?php

namespace Lootwright\GameAdapters\Shared\Pob;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final class PobEnvelopeDecoder
{
    public function decode(string $input, ImportLimits $limits): DomainResult
    {
        if (strlen($input) > $limits->inputBytes) {
            return $this->failure(DomainErrorCode::InputTooLarge, 'The submitted build input exceeds the request limit.');
        }

        $trimmed = trim($input);

        if ($trimmed === '') {
            return $this->failure(DomainErrorCode::InvalidEncoding, 'The submitted build input is empty.');
        }

        if (preg_match('~^https?://~i', $trimmed) === 1) {
            $pobbMatch = [];

            if (preg_match('~\Ahttps://(?:www\.)?pobb\.in/([A-Za-z0-9_-]+)\z~iD', $trimmed, $pobbMatch) !== 1) {
                return $this->failure(
                    DomainErrorCode::UnsupportedInput,
                    'Lootwright accepts only a canonical HTTPS pobb.in share-code URL and never fetches build URLs.',
                );
            }

            // A canonical pobb.in path is a user-pasted Base64URL share code.
            // Extract it locally; this adapter must never make an outbound request.
            $trimmed = $pobbMatch[1];
        }

        if (str_starts_with(ltrim($trimmed), '<')) {
            return $this->xml($trimmed, $limits, BuildInputType::DecodedXml);
        }

        $code = preg_replace('/\s+/', '', $trimmed);

        if (! is_string($code)
            || preg_match('/^[A-Za-z0-9+\/_=-]+$/D', $code) !== 1
            || str_contains(substr($code, 0, -2), '=')
        ) {
            return $this->failure(DomainErrorCode::InvalidEncoding, 'The build code is not valid Base64 or Base64URL text.');
        }

        $code = strtr(rtrim($code, '='), '-_', '+/');
        $remainder = strlen($code) % 4;

        if ($remainder === 1) {
            return $this->failure(DomainErrorCode::InvalidEncoding, 'The build code has invalid Base64 padding.');
        }

        $code .= str_repeat('=', (4 - $remainder) % 4);
        $compressed = base64_decode($code, true);

        if (! is_string($compressed)) {
            return $this->failure(DomainErrorCode::InvalidEncoding, 'The build code could not be decoded safely.');
        }

        if (strlen($compressed) > $limits->compressedBytes) {
            return $this->failure(DomainErrorCode::InputTooLarge, 'The compressed build exceeds the compressed-size limit.');
        }

        $xml = @zlib_decode($compressed, $limits->xmlBytes);

        if (! is_string($xml)) {
            $code = $this->hasZlibHeader($compressed)
                ? DomainErrorCode::DecompressionLimit
                : DomainErrorCode::InvalidCompression;
            $message = $code === DomainErrorCode::DecompressionLimit
                ? 'The build expands beyond the decompression limit.'
                : 'The build code does not contain a valid zlib stream.';

            return $this->failure($code, $message);
        }

        if (strlen($compressed) === 0
            || strlen($xml) > strlen($compressed) * $limits->expansionRatio
        ) {
            return $this->failure(DomainErrorCode::DecompressionLimit, 'The build exceeds the permitted decompression ratio.');
        }

        return $this->xml($xml, $limits, BuildInputType::PobShareCode);
    }

    private function xml(string $xml, ImportLimits $limits, BuildInputType $inputType): DomainResult
    {
        $xml = trim($xml);

        if (strlen($xml) > $limits->xmlBytes) {
            return $this->failure(DomainErrorCode::InputTooLarge, 'The XML build exceeds the decoded-size limit.');
        }

        return DomainResult::success(new DecodedPobInput(
            $xml,
            hash('sha256', $xml),
            $inputType,
        ));
    }

    private function hasZlibHeader(string $data): bool
    {
        if (strlen($data) < 2) {
            return false;
        }

        $cmf = ord($data[0]);
        $flg = ord($data[1]);

        return ($cmf & 0x0F) === 8 && (($cmf << 8) + $flg) % 31 === 0;
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }
}
