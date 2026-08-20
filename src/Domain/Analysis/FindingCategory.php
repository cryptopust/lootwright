<?php

namespace Lootwright\Domain\Analysis;

enum FindingCategory: string
{
    case DataQuality = 'data_quality';
    case Equipment = 'equipment';
    case Defence = 'defence';
    case Resources = 'resources';
    case Skills = 'skills';
    case PassiveTree = 'passive_tree';
}
