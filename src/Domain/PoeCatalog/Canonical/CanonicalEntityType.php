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
    case TagDefinition = 'tag_definition';
    case RequirementDefinition = 'requirement_definition';
    case DamageTypeDefinition = 'damage_type_definition';
    case AilmentDefinition = 'ailment_definition';
    case DefensiveMechanicDefinition = 'defensive_mechanic_definition';
    case OffensiveMechanicDefinition = 'offensive_mechanic_definition';
    case ReservationMechanicDefinition = 'reservation_mechanic_definition';
    case AttributeDefinition = 'attribute_definition';
    case JewelDefinition = 'jewel_definition';
    case ClusterDefinition = 'cluster_definition';
    case EditionMechanicDefinition = 'edition_mechanic_definition';
    case TradeVocabularyDefinition = 'trade_vocabulary_definition';
}
