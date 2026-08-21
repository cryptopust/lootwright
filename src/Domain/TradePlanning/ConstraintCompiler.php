<?php

namespace Lootwright\Domain\TradePlanning;

/** Renders descriptive filters only; it deliberately has no Trade API format. */
final class ConstraintCompiler
{
    /** @param array<int,array<string,mixed>> $filters */
    public function compile(string $heading, array $filters): string
    {
        $lines = [$heading.':'];
        if ($filters === []) {
            return $heading.':\n- none';
        }
        foreach ($filters as $filter) {
            $label = (string) ($filter['label'] ?? 'unknown filter');
            $minimum = isset($filter['minimum']) ? ' (minimum '.(string) $filter['minimum'].')' : '';
            $weight = isset($filter['weight']) ? ' [weight '.(string) $filter['weight'].']' : '';
            $lines[] = '- '.$label.$minimum.$weight;
        }

        return implode("\n", $lines);
    }
}
