<?php

namespace Lootwright\Application\ExternalSources\DTO;

enum SourceFreshness: string { case Fresh = 'fresh'; case StaleUsable = 'stale_usable'; case Expired = 'expired'; case Unavailable = 'unavailable'; }
