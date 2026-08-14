<?php

namespace Lootwright\Application\Workflow;

enum AnalysisState: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case ClarificationRequired = 'clarification_required';
    case Completed = 'completed';
    case Failed = 'failed';
    case PolicyBlocked = 'policy_blocked';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::PolicyBlocked], true);
    }
}
