<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

use SkyFi\Workflow\Contracts\RuleEvaluatorContract;
use SkyFi\Workflow\DTOs\ConditionNode;

final class RuleEvaluator implements RuleEvaluatorContract
{
    public function evaluate(?array $conditions, array $payload): bool
    {
        if ($conditions === null || $conditions === []) {
            return true;
        }

        $node = ConditionNode::fromArray($conditions);

        return $this->evaluateNode($node, $payload);
    }

    private function evaluateNode(ConditionNode $node, array $payload): bool
    {
        if ($node->isGroup()) {
            $logic = strtoupper((string) $node->logic);
            if ($logic === 'OR') {
                foreach ($node->rules as $rule) {
                    if ($this->evaluateNode($rule, $payload)) {
                        return true;
                    }
                }

                return $node->rules === [];
            }

            foreach ($node->rules as $rule) {
                if (!$this->evaluateNode($rule, $payload)) {
                    return false;
                }
            }

            return true;
        }

        return $this->evaluateLeaf($node, $payload);
    }

    private function evaluateLeaf(ConditionNode $node, array $payload): bool
    {
        $field = (string) ($node->field ?? '');
        $operator = (string) ($node->operator ?? 'equals');
        $expected = $node->value;
        $actual = $this->resolvePath($payload, $field);

        return match ($operator) {
            'equals' => $this->looseEquals($actual, $expected),
            'not_equals' => !$this->looseEquals($actual, $expected),
            'contains' => $this->contains($actual, $expected),
            'starts_with' => is_scalar($actual) && str_starts_with((string) $actual, (string) $expected),
            'ends_with' => is_scalar($actual) && str_ends_with((string) $actual, (string) $expected),
            'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            'between' => $this->between($actual, $expected),
            'is_empty' => $actual === null || $actual === '' || $actual === [] || $actual === false,
            'is_not_empty' => !($actual === null || $actual === '' || $actual === [] || $actual === false),
            default => false,
        };
    }

    private function looseEquals(mixed $actual, mixed $expected): bool
    {
        if (is_bool($expected)) {
            return (bool) $actual === $expected;
        }
        if (is_numeric($actual) && is_numeric($expected)) {
            return (float) $actual === (float) $expected;
        }

        return (string) $actual === (string) $expected;
    }

    private function contains(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            return in_array($expected, $actual, false);
        }
        if (is_scalar($actual)) {
            return str_contains((string) $actual, (string) $expected);
        }

        return false;
    }

    private function between(mixed $actual, mixed $expected): bool
    {
        if (!is_numeric($actual)) {
            return false;
        }
        $min = null;
        $max = null;
        if (is_array($expected)) {
            $min = $expected['min'] ?? $expected[0] ?? null;
            $max = $expected['max'] ?? $expected[1] ?? null;
        }
        if (!is_numeric($min) || !is_numeric($max)) {
            return false;
        }

        $value = (float) $actual;

        return $value >= (float) $min && $value <= (float) $max;
    }

    /** @param array<string, mixed> $payload */
    private function resolvePath(array $payload, string $path): mixed
    {
        if ($path === '') {
            return null;
        }
        $segments = explode('.', $path);
        $current = $payload;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
