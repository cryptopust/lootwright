<?php
namespace Lootwright\Application\ExternalSources\Ports;
interface SourcePolicyGate { public function permits(string $operation): bool; }
