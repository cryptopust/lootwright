<?php

namespace Lootwright\Domain\Analysis;

enum AnalysisStatus: string
{
    case Complete = 'complete';
    case Unsupported = 'unsupported';
    case Unavailable = 'unavailable';
}
