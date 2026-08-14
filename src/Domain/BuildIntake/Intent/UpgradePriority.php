<?php

namespace Lootwright\Domain\BuildIntake\Intent;

enum UpgradePriority: int
{
    case Critical = 400;
    case High = 300;
    case Medium = 200;
    case Low = 100;
}
