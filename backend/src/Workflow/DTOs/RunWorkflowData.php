<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DTOs;

final class RunWorkflowData
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload = [],
        public readonly bool $dryRun = false,
        public readonly ?int $versionId = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $attrs = $input['data']['attributes'] ?? $input;
        $payload = $attrs['payload'] ?? $attrs['trigger_payload'] ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }

        return new self(
            payload: $payload,
            dryRun: (bool) ($attrs['dry_run'] ?? false),
            versionId: isset($attrs['version_id']) ? (int) $attrs['version_id'] : null,
        );
    }
}
