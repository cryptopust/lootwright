<?php

namespace Lootwright\GameAdapters\Shared\ItemText;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\BuildSourceMetadata;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\ImportProvenance;
use Lootwright\Domain\BuildIntake\Import\ImportWarning;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\BuildIntake\Import\PropertySupportStatus;
use Lootwright\Domain\BuildIntake\Ports\ItemTextBuildImporter;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

abstract class AbstractItemTextImporter implements ItemTextBuildImporter
{
    public const PARSER_VERSION = '1.0.0';

    abstract public function edition(): GameEdition;

    abstract protected function beta(): bool;

    public function supports(BuildInputType $inputType): bool
    {
        return $inputType === BuildInputType::ItemText;
    }

    public function import(string $input, ImportLimits $limits): DomainResult
    {
        if (strlen($input) > $limits->textBytes) {
            return $this->failure(DomainErrorCode::InputTooLarge, 'The submitted item text exceeds the text limit.');
        }

        if (! mb_check_encoding($input, 'UTF-8') || str_contains($input, "\0")) {
            return $this->failure(DomainErrorCode::InvalidEncoding, 'The submitted item text must be valid UTF-8 without NUL bytes.');
        }

        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $input));

        if ($normalized === '') {
            return $this->failure(DomainErrorCode::UnsupportedInput, 'The submitted item text is empty.');
        }

        $opposite = $this->edition() === GameEdition::Poe1 ? 'poe2' : 'poe1';

        if (preg_match('/(?:^|[^a-z0-9])'.preg_quote($opposite, '/').'[.:][a-z0-9._-]+/i', $normalized) === 1) {
            return $this->failure(DomainErrorCode::EditionMismatch, 'The item text contains an identifier scoped to the other game edition.');
        }

        $lines = explode("\n", $normalized);

        if (count($lines) > $limits->itemTextLines) {
            return $this->failure(DomainErrorCode::InputTooLarge, 'The submitted item text exceeds the line-count limit.');
        }

        foreach ($lines as $line) {
            if (strlen($line) > $limits->lineBytes || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $line) === 1) {
                return $this->failure(DomainErrorCode::InputTooLarge, 'The submitted item text contains an invalid or oversized line.');
            }
        }

        $checksum = hash('sha256', $normalized);
        [$item, $modifiers, $warnings] = $this->normalizeLines($lines, $checksum);
        $propertySupport = [
            'game_edition' => PropertySupportStatus::PartiallySupported,
            'items' => PropertySupportStatus::PartiallySupported,
            'item_modifiers' => $modifiers === [] ? PropertySupportStatus::Unknown : PropertySupportStatus::PartiallySupported,
            'game_version' => PropertySupportStatus::Unknown,
            'level' => PropertySupportStatus::Unknown,
            'class' => PropertySupportStatus::Unknown,
            'ascendancy' => PropertySupportStatus::Unknown,
            'attributes' => PropertySupportStatus::Unknown,
            'life' => PropertySupportStatus::Unknown,
            'energy_shield' => PropertySupportStatus::Unknown,
            'mana' => PropertySupportStatus::Unknown,
            'armour' => PropertySupportStatus::Unknown,
            'evasion' => PropertySupportStatus::Unknown,
            'resistances' => PropertySupportStatus::Unknown,
            'skills' => PropertySupportStatus::Unknown,
            'supports' => PropertySupportStatus::Unknown,
            'auras' => PropertySupportStatus::Unknown,
            'passive_nodes' => PropertySupportStatus::Unknown,
            'keystones' => PropertySupportStatus::Unknown,
            'jewels' => PropertySupportStatus::Unknown,
            'clusters' => PropertySupportStatus::Unknown,
            'configuration' => PropertySupportStatus::Unknown,
        ];
        $provenance = new ImportProvenance(
            'USER-ITEM-TEXT-001',
            'user-submission',
            null,
            self::PARSER_VERSION,
        );
        $canonical = new CanonicalImportedBuild(
            $this->edition(),
            null,
            null,
            null,
            null,
            [],
            [],
            [],
            [$item],
            [],
            [],
            '',
            $this->beta(),
            itemModifiers: $modifiers,
            propertySupport: $propertySupport,
            warnings: $warnings,
            sourceMetadata: new BuildSourceMetadata(
                'USER-ITEM-TEXT-001',
                BuildInputType::ItemText,
                $this->edition(),
                'explicit_expected_edition; item text does not independently prove edition',
                $checksum,
                self::PARSER_VERSION,
            ),
        );

        return DomainResult::success(new PobImportResult(
            $canonical,
            $warnings,
            [],
            self::PARSER_VERSION,
            $checksum,
            $provenance,
        ));
    }

    /** @param list<string> $lines
     * @return array{array<string, mixed>, list<array<string, mixed>>, list<ImportWarning>}
     */
    private function normalizeLines(array $lines, string $checksum): array
    {
        $rarity = null;
        $itemLevel = null;
        $sockets = null;
        $header = [];
        $modifiers = [];
        $warnings = [];
        $afterSeparator = false;

        foreach ($lines as $index => $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^-{8,}$/D', $line) === 1) {
                $afterSeparator = true;

                continue;
            }

            if (str_starts_with($line, 'Item Class:')) {
                continue;
            }

            if (str_starts_with($line, 'Rarity:')) {
                if ($rarity !== null) {
                    $warnings[] = new ImportWarning('duplicate_item_rarity', 'A duplicate rarity line was ignored.', '/item_text/'.($index + 1));

                    continue;
                }

                $rarity = $this->valueAfterColon($line);

                continue;
            }

            if (str_starts_with($line, 'Item Level:')) {
                $candidate = $this->valueAfterColon($line);
                $itemLevel = preg_match('/^[0-9]{1,3}$/D', $candidate) === 1 ? (int) $candidate : null;

                if ($itemLevel === null) {
                    $warnings[] = new ImportWarning('invalid_item_level', 'An invalid item level was left unknown.', '/item_text/'.($index + 1));
                }

                continue;
            }

            if (str_starts_with($line, 'Sockets:')) {
                $sockets = $this->valueAfterColon($line);

                continue;
            }

            if (preg_match('/^[A-Za-z][A-Za-z ]{1,40}:\s*.+$/D', $line) === 1) {
                continue;
            }

            if (! $afterSeparator && count($header) < 2) {
                $header[] = mb_substr($line, 0, 256, 'UTF-8');

                continue;
            }

            $modifiers[] = [
                'position' => count($modifiers) + 1,
                'observed_text_untrusted' => mb_substr($line, 0, 512, 'UTF-8'),
                'canonical_modifier_id' => null,
                'support' => PropertySupportStatus::PartiallySupported->value,
            ];
        }

        $item = [
            'id' => $this->edition()->value.'.user.item.'.substr($checksum, 0, 24),
            'source_checksum_sha256' => $checksum,
            'rarity_observed' => $rarity,
            'display_name_observed' => $header[0] ?? null,
            'base_name_observed' => $header[1] ?? ($header[0] ?? null),
            'item_level_observed' => $itemLevel,
            'sockets_observed' => $sockets,
            'raw_text_retained' => false,
        ];

        return [$item, $modifiers, $warnings];
    }

    private function valueAfterColon(string $line): string
    {
        $position = strpos($line, ':');

        return $position === false ? '' : mb_substr(trim(substr($line, $position + 1)), 0, 256, 'UTF-8');
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }
}
