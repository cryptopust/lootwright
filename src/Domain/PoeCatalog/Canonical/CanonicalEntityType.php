<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

enum CanonicalEntityType: string
{
    case CharacterClass = 'character_class';
    case Ascendancy = 'ascendancy';
    case PassiveNode = 'passive_node';
    case Keystone = 'keystone';
    case SkillGem = 'skill_gem';
    case SupportGem = 'support_gem';
    case ItemBase = 'item_base';
    case UniqueItem = 'unique_item';
    case ModifierDefinition = 'modifier_definition';
    case StatDefinition = 'stat_definition';
    case ContentGoalDefinition = 'content_goal_definition';
}
