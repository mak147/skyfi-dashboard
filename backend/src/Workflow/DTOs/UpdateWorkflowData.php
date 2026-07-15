<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DTOs;

final class UpdateWorkflowData
{
    /**
     * @param array<string, mixed>|null $definition
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $status = null,
        public readonly ?bool $isEnabled = null,
        public readonly ?string $scheduleMode = null,
        public readonly ?string $cronExpression = null,
        public readonly ?int $delaySeconds = null,
        public readonly ?int $maxRetries = null,
        public readonly ?int $retryDelaySeconds = null,
        public readonly ?array $definition = null,
        public readonly ?string $changelog = null,
        public readonly bool $publishVersion = true,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $attrs = $input['data']['attributes'] ?? $input;
        $definition = array_key_exists('definition', $attrs) && is_array($attrs['definition'])
            ? $attrs['definition']
            : null;

        return new self(
            name: array_key_exists('name', $attrs) ? trim((string) $attrs['name']) : null,
            description: array_key_exists('description', $attrs)
                ? ($attrs['description'] !== null ? trim((string) $attrs['description']) : null)
                : null,
            status: array_key_exists('status', $attrs) ? (string) $attrs['status'] : null,
            isEnabled: array_key_exists('is_enabled', $attrs) ? (bool) $attrs['is_enabled'] : null,
            scheduleMode: array_key_exists('schedule_mode', $attrs) ? (string) $attrs['schedule_mode'] : null,
            cronExpression: array_key_exists('cron_expression', $attrs)
                ? ($attrs['cron_expression'] !== null ? (string) $attrs['cron_expression'] : null)
                : null,
            delaySeconds: array_key_exists('delay_seconds', $attrs) ? (int) $attrs['delay_seconds'] : null,
            maxRetries: array_key_exists('max_retries', $attrs) ? (int) $attrs['max_retries'] : null,
            retryDelaySeconds: array_key_exists('retry_delay_seconds', $attrs)
                ? (int) $attrs['retry_delay_seconds']
                : null,
            definition: $definition,
            changelog: array_key_exists('changelog', $attrs) ? (string) $attrs['changelog'] : null,
            publishVersion: (bool) ($attrs['publish_version'] ?? true),
        );
    }
}
