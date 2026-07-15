<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DTOs;

final class ConditionNode
{
    /**
     * @param list<self> $rules
     * @param mixed $value
     */
    public function __construct(
        public readonly ?string $logic = null,
        public readonly array $rules = [],
        public readonly ?string $field = null,
        public readonly ?string $operator = null,
        public readonly mixed $value = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $rules = [];
        if (isset($data['rules']) && is_array($data['rules'])) {
            foreach ($data['rules'] as $rule) {
                if (is_array($rule)) {
                    $rules[] = self::fromArray($rule);
                }
            }
        }

        return new self(
            logic: isset($data['logic']) ? strtoupper((string) $data['logic']) : null,
            rules: $rules,
            field: isset($data['field']) ? (string) $data['field'] : (isset($data['field_path']) ? (string) $data['field_path'] : null),
            operator: isset($data['operator']) ? (string) $data['operator'] : null,
            value: $data['value'] ?? null,
        );
    }

    public function isGroup(): bool
    {
        return $this->logic !== null && $this->rules !== [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->isGroup()) {
            return [
                'logic' => $this->logic,
                'rules' => array_map(static fn (self $r): array => $r->toArray(), $this->rules),
            ];
        }

        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }
}
