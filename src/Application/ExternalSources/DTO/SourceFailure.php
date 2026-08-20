<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class SourceFailure { public function __construct(public string $code, public bool $retryable) {} }
