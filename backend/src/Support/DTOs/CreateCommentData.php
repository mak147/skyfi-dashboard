<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class CreateCommentData
{
    public function __construct(
        public readonly string $type,
        public readonly string $body,
        public readonly ?int $customerId = null,
    ) {}
    /** @param array<string,mixed> $d */ public static function fromArray(
        array $d,
    ): self {
        return new self(
            (string) ($d["type"] ?? "staff_reply"),
            trim((string) ($d["body"] ?? "")),
            isset($d["customer_id"]) ? (int) $d["customer_id"] : null,
        );
    }
}
