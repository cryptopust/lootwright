<?php

namespace Lootwright\Domain\Rulesets;

enum ProvenanceStatus: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Invalid = 'invalid';
}
