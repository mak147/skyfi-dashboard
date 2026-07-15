<?php

declare(strict_types=1);

namespace SkyFi\Portal\DTOs;

final class ReplyTicketData
{
    public function __construct(
        public readonly string $body,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            trim((string) ($data['body'] ?? '')),
        );
    }
}
