<?php

namespace Lootwright\Domain\PolicyProvenance;

enum Capability: string
{
    case Import = 'import';
    case TransientProcess = 'transient_process';
    case PersistentStore = 'persistent_store';
    case PublicDisplay = 'public_display';
    case DerivativeAnalysis = 'derivative_analysis';
    case LinkOut = 'link_out';
    case LiveFetch = 'live_fetch';
    case Redistribution = 'redistribution';
    case MonetizedHosting = 'monetized_hosting';
}
