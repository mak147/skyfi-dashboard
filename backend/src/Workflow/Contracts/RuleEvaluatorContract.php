<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

interface RuleEvaluatorContract
{
    /**
     * @param array<string, mixed>|null $conditions
     * @param array<string, mixed> $payload
     */
    public function evaluate(?array $conditions, array $payload): bool;
}
