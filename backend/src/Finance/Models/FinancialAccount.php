<?php

declare(strict_types=1);
namespace SkyFi\Finance\Models;

final class FinancialAccount
{
    public function __construct(private readonly array $attributes) {}
    public function id(): int { return (int) $this->attributes['id']; }
    public function balance(): string { return number_format((float) $this->attributes['balance'], 2, '.', ''); }
    
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $a = $this->attributes;
        foreach (['id', 'chart_of_account_id'] as $key) {
            if (isset($a[$key])) $a[$key] = (int) $a[$key];
        }
        if (array_key_exists('balance', $a)) {
            $a['balance'] = number_format((float) $a['balance'], 2, '.', '');
        }
        return $a;
    }
    public static function fromRow(array $row): self { return new self($row); }
}
