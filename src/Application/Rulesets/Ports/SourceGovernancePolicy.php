<?php

namespace Lootwright\Application\Rulesets\Ports;

interface SourceGovernancePolicy
{
    /** @param list<string> $conditions */
    public function permitsImport(string $sourceCode, string $sourceVersion, string $operation, array $conditions = []): bool;

    public function permitsActivation(string $sourceCode, string $sourceVersion): bool;
}
