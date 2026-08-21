<?php

namespace Lootwright\Domain\Recommendations;

enum ConstraintStrength: string
{
    case Hard = 'hard';
    case Preference = 'preference';
}
