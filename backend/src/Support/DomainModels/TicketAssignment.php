<?php

declare(strict_types=1);
namespace SkyFi\Support\DomainModels;
final class TicketAssignment
{
    /** @param array<string, mixed> $attributes */ public function __construct(
        private readonly array $attributes,
    ) {}
    /** @return array<string, mixed> */ public function toArray(): array
    {
        return $this->attributes;
    }
    /** @param array<string, mixed> $row */ public static function fromRow(
        array $row,
    ): self {
        return new self($row);
    }
}
