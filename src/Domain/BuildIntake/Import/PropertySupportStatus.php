<?php

namespace Lootwright\Domain\BuildIntake\Import;

enum PropertySupportStatus: string
{
    case Supported = 'supported';
    case PartiallySupported = 'partially_supported';
    case Unsupported = 'unsupported';
    case Unknown = 'unknown';
}
