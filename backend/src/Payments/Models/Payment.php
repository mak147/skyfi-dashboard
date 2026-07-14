<?php

declare(strict_types=1);
namespace SkyFi\Payments\Models;

final class Payment
{
    public function __construct(private readonly array $attributes) {}
    public function id(): int { return (int) $this->attributes['id']; }
    public function status(): string { return (string) $this->attributes['status']; }
    public function customerId(): int { return (int) $this->attributes['customer_id']; }
    public function amount(): string { return number_format((float) $this->attributes['amount'], 2, '.', ''); }
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $a = $this->attributes;
        foreach (['amount','tax_amount','discount_amount','adjustment_amount','applied_amount','refunded_amount','available_amount'] as $key) {
            if (array_key_exists($key, $a)) $a[$key] = number_format((float) $a[$key], 2, '.', '');
        }
        foreach (['id','customer_id','connection_id','payment_method_id','payment_account_id','collected_by','created_by','updated_by'] as $key) {
            if (isset($a[$key])) $a[$key] = (int) $a[$key];
        }
        foreach (['allocations','refunds','activities','attachments'] as $key) {
            if (!isset($a[$key])) $a[$key] = [];
        }
        return $a;
    }
    public static function fromRow(array $row): self { return new self($row); }
}
