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
    case Offence = 'offence';
    case Resistances = 'resistances';
    case Attributes = 'attributes';
    case SkillConfiguration = 'skill_configuration';
    case SupportCompatibility = 'support_compatibility';
    case AuraReservation = 'aura_reservation';
    case Mobility = 'mobility';
    case Recovery = 'recovery';
    case Mitigation = 'mitigation';
    case Avoidance = 'avoidance';
    case ItemConflicts = 'item_conflicts';
    case PassiveConflicts = 'passive_conflicts';
    case KeystoneConflicts = 'keystone_conflicts';
    case BuildDependencies = 'build_dependencies';
    case ContentGoalSuitability = 'content_goal_suitability';
}
