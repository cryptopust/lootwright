<?php

namespace Lootwright\Domain\Analysis;

enum FindingSeverity: int
{
    case Critical = 400;
    case Warning = 300;
    case Opportunity = 200;
    case Information = 100;
}
