<?php

namespace Lootwright\Domain\Analysis;

interface AnalysisRule
{
    public function id(): string;

    public function category(): FindingCategory;

    /** @return list<Finding> */
    public function evaluate(AnalysisContext $context): array;
}
