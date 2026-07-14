<?php

declare(strict_types=1);

namespace SkyFi\Support\DomainModels;

final class SupportTicket
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    public function id(): int
    {
        return (int) $this->attributes["id"];
    }
    public function status(): string
    {
        return (string) $this->attributes["status"];
    }
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            ...$this->attributes,
            "attachments" => [],
            "attachments_supported" => false,
        ];
    }
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
