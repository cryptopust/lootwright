<?php

namespace Lootwright\Domain\PolicyProvenance;

enum PolicyDecision: string
{
    case Allow = 'allow';
    case Deny = 'deny';
    case RequireReview = 'require_review';
}
