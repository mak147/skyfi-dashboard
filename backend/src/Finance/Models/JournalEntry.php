<?php

declare(strict_types=1);
namespace SkyFi\Finance\Models;

final class JournalEntry
{
    public function __construct(private readonly array $attributes) {}
    public function id(): int { return (int) $this->attributes['id']; }
    public function transactionId(): string { return (string) $this->attributes['transaction_id']; }
    
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $a = $this->attributes;
        foreach (['id', 'source_id', 'created_by'] as $key) {
            if (isset($a[$key])) $a[$key] = (int) $a[$key];
        }
        if (!isset($a['lines'])) {
            $a['lines'] = [];
        }
        return $a;
    }
    public static function fromRow(array $row): self { return new self($row); }
}
