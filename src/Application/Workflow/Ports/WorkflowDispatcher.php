<?php

namespace Lootwright\Application\Workflow\Ports;

interface WorkflowDispatcher
{
    public function parse(string $artifactId): void;

    public function analyze(string $analysisId): void;
}
