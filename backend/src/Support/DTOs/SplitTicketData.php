<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class SplitTicketData
{
    public function __construct(
        public readonly string $subject,
        public readonly string $description,
        public readonly ?int $categoryId,
        public readonly ?string $priority,
        public readonly string $reason,
    ) {}
    /** @param array<string,mixed> $d */ public static function fromArray(
        array $d,
    ): self {
        return new self(
            trim((string) ($d["subject"] ?? "")),
            trim((string) ($d["description"] ?? "")),
            isset($d["category_id"]) ? (int) $d["category_id"] : null,
            isset($d["priority"]) ? (string) $d["priority"] : null,
            trim((string) ($d["reason"] ?? "")),
        );
    }
}
