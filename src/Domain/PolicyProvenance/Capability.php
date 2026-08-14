<?php

namespace Lootwright\Domain\PolicyProvenance;

enum Capability: string
{
    case UserBuildInput = 'user_build_input';
    case RulesetImport = 'ruleset_import';
    case IntentExtraction = 'intent_extraction';
    case ResultExplanation = 'result_explanation';
    case Funding = 'funding';
}
