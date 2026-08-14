<?php

namespace Lootwright\Application\Workflow\Ports;

interface ArtifactStorage
{
    public function put(string $key, string $contents): void;

    public function get(string $key): string;

    public function delete(string $key): void;
}
