<?php

namespace Lootwright\Domain\Rulesets;

enum RulesetCompatibilityStatus: string
{
    case Compatible = 'compatible';
    case UnsupportedPatch = 'unsupported_patch';
    case Outdated = 'outdated';
    case IncompatibleParser = 'incompatible_parser';
    case Unavailable = 'unavailable';
    case InvalidProvenance = 'invalid_provenance';
    case FixtureRejected = 'fixture_rejected';
}
