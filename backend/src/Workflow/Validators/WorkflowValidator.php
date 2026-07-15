<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Validators;

use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Workflow\DTOs\CreateWorkflowData;
use SkyFi\Workflow\DTOs\UpdateWorkflowData;
use SkyFi\Workflow\Services\WorkflowCatalog;

final class WorkflowValidator
{
    public function __construct(private readonly WorkflowCatalog $catalog) {}

    public function create(CreateWorkflowData $data): void
    {
        $errors = [];
        if ($data->name === '') {
            $errors[] = $this->err('name', 'Workflow name is required.');
        }
        if (!in_array($data->status, $this->catalog->statuses(), true)) {
            $errors[] = $this->err('status', 'Invalid workflow status.');
        }
        if (!in_array($data->scheduleMode, $this->catalog->scheduleModes(), true)) {
            $errors[] = $this->err('schedule_mode', 'Invalid schedule mode.');
        }
        $errors = array_merge($errors, $this->validateDefinition($data->definition, $data->scheduleMode));
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function update(UpdateWorkflowData $data): void
    {
        $errors = [];
        if ($data->name !== null && $data->name === '') {
            $errors[] = $this->err('name', 'Workflow name cannot be empty.');
        }
        if ($data->status !== null && !in_array($data->status, $this->catalog->statuses(), true)) {
            $errors[] = $this->err('status', 'Invalid workflow status.');
        }
        if ($data->scheduleMode !== null && !in_array($data->scheduleMode, $this->catalog->scheduleModes(), true)) {
            $errors[] = $this->err('schedule_mode', 'Invalid schedule mode.');
        }
        if ($data->definition !== null) {
            $errors = array_merge(
                $errors,
                $this->validateDefinition($data->definition, $data->scheduleMode ?? 'immediate'),
            );
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array<string, mixed> $definition
     * @return list<array<string, mixed>>
     */
    private function validateDefinition(array $definition, string $scheduleMode): array
    {
        $errors = [];
        $trigger = $definition['trigger'] ?? null;
        if (!is_array($trigger) || trim((string) ($trigger['event_key'] ?? '')) === '') {
            $errors[] = $this->err('definition.trigger.event_key', 'A trigger event key is required.');
        }

        $actions = $definition['actions'] ?? null;
        if (!is_array($actions) || $actions === []) {
            $errors[] = $this->err('definition.actions', 'At least one action is required.');
        } else {
            $allowed = array_column($this->catalog->actions(), 'type');
            foreach ($actions as $index => $action) {
                if (!is_array($action)) {
                    $errors[] = $this->err("definition.actions.{$index}", 'Action must be an object.');
                    continue;
                }
                $type = (string) ($action['type'] ?? $action['action_type'] ?? '');
                if ($type === '' || !in_array($type, $allowed, true)) {
                    $errors[] = $this->err("definition.actions.{$index}.type", "Unsupported action type: {$type}");
                }
            }
        }

        if (isset($definition['conditions']) && is_array($definition['conditions'])) {
            $errors = array_merge($errors, $this->validateConditions($definition['conditions'], 'definition.conditions'));
        }

        if (in_array($scheduleMode, ['cron', 'recurring'], true)) {
            $cron = $definition['schedule']['cron'] ?? null;
            if (!is_string($cron) || trim($cron) === '') {
                // schedule may also live on the workflow root; soft check only
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $node
     * @return list<array<string, mixed>>
     */
    private function validateConditions(array $node, string $pointer): array
    {
        $errors = [];
        if (isset($node['logic']) || isset($node['rules'])) {
            $logic = strtoupper((string) ($node['logic'] ?? 'AND'));
            if (!in_array($logic, ['AND', 'OR'], true)) {
                $errors[] = $this->err($pointer . '.logic', 'Group logic must be AND or OR.');
            }
            $rules = $node['rules'] ?? [];
            if (!is_array($rules)) {
                $errors[] = $this->err($pointer . '.rules', 'Condition rules must be an array.');

                return $errors;
            }
            foreach ($rules as $i => $rule) {
                if (is_array($rule)) {
                    $errors = array_merge($errors, $this->validateConditions($rule, $pointer . ".rules.{$i}"));
                }
            }

            return $errors;
        }

        $operator = (string) ($node['operator'] ?? '');
        $allowedOps = array_column($this->catalog->operators(), 'id');
        if ($operator === '' || !in_array($operator, $allowedOps, true)) {
            $errors[] = $this->err($pointer . '.operator', 'Invalid condition operator.');
        }
        $field = (string) ($node['field'] ?? $node['field_path'] ?? '');
        if ($field === '' && !in_array($operator, ['is_empty', 'is_not_empty'], true)) {
            $errors[] = $this->err($pointer . '.field', 'Condition field is required.');
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    private function err(string $pointer, string $detail): array
    {
        return [
            'code' => 'validation_error',
            'detail' => $detail,
            'source' => ['pointer' => '/data/attributes/' . str_replace('.', '/', $pointer)],
        ];
    }
}
