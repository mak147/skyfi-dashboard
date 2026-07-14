<?php

declare(strict_types=1);
namespace SkyFi\Finance\Models;

final class Expense
{
    public function __construct(private readonly array $attributes) {}
    public function id(): int { return (int) $this->attributes['id']; }
    
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $a = $this->attributes;
        foreach (['id', 'financial_account_id', 'chart_of_account_id', 'created_by'] as $key) {
            if (isset($a[$key])) $a[$key] = (int) $a[$key];
        }
        if (array_key_exists('amount', $a)) {
            $a['amount'] = number_format((float) $a['amount'], 2, '.', '');
        }
        return $a;
    }
    public static function fromRow(array $row): self { return new self($row); }
}
