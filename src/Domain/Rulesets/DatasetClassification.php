<?php

namespace Lootwright\Domain\Rulesets;

enum DatasetClassification: string
{
    case ApprovedImport = 'approved_import';
    case Fixture = 'fixture';
    case Unavailable = 'unavailable';
}
