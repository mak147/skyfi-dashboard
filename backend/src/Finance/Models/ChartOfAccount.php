<?php

declare(strict_types=1);
namespace SkyFi\Finance\Models;

final class ChartOfAccount
{
    public function __construct(private readonly array $attributes) {}
    public function id(): int { return (int) $this->attributes['id']; }
    public function accountNumber(): string { return (string) $this->attributes['account_number']; }
    public function name(): string { return (string) $this->attributes['name']; }
    public function type(): string { return (string) $this->attributes['type']; }
    public function normalBalance(): string { return (string) $this->attributes['normal_balance']; }
    
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $a = $this->attributes;
        foreach (['id', 'parent_id'] as $key) {
            if (isset($a[$key])) $a[$key] = (int) $a[$key];
        }
        return $a;
    }
    public static function fromRow(array $row): self { return new self($row); }
}
