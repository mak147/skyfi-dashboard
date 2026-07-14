<?php

declare(strict_types=1);
namespace SkyFi\Support\DomainModels;
final class SlaPolicy
{
    /** @param array<string, mixed> $attributes */ public function __construct(
        private readonly array $attributes,
    ) {}
    public function id(): int
    {
        return (int) $this->attributes["id"];
    }
    public function responseMinutes(): int
    {
        return (int) $this->attributes["response_minutes"];
    }
    public function resolutionMinutes(): int
    {
        return (int) $this->attributes["resolution_minutes"];
    }
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
