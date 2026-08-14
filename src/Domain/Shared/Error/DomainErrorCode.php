<?php

namespace Lootwright\Domain\Shared\Error;

enum DomainErrorCode: string
{
    case InvalidIdentifier = 'invalid_identifier';
    case InvalidVersion = 'invalid_version';
    case InvalidLocale = 'invalid_locale';
    case InvalidAmount = 'invalid_amount';
    case InvalidConfidence = 'invalid_confidence';
    case InvalidChecksum = 'invalid_checksum';
    case InvalidValue = 'invalid_value';
    case EmptyCollection = 'empty_collection';
    case DuplicateValue = 'duplicate_value';
    case EditionMismatch = 'edition_mismatch';
    case RealmMismatch = 'realm_mismatch';
    case PatchMismatch = 'patch_mismatch';
    case LeagueMismatch = 'league_mismatch';
    case ParserMismatch = 'parser_mismatch';
    case RulesetMismatch = 'ruleset_mismatch';
    case AnalysisMismatch = 'analysis_mismatch';
    case ClarificationRequired = 'clarification_required';
    case UnsupportedSerialization = 'unsupported_serialization';
}
