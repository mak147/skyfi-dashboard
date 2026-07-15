<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DTOs;

final class CreateWorkflowData
{
    /**
     * @param array<string, mixed> $definition
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $status,
        public readonly bool $isEnabled,
        public readonly string $scheduleMode,
        public readonly ?string $cronExpression,
        public readonly int $delaySeconds,
        public readonly int $maxRetries,
        public readonly int $retryDelaySeconds,
        public readonly array $definition,
        public readonly ?string $changelog = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $attrs = $input['data']['attributes'] ?? $input;
        $definition = $attrs['definition'] ?? [];
        if (!is_array($definition)) {
            $definition = [];
        }

        return new self(
            name: trim((string) ($attrs['name'] ?? '')),
            description: isset($attrs['description']) ? trim((string) $attrs['description']) : null,
            status: (string) ($attrs['status'] ?? 'draft'),
            isEnabled: (bool) ($attrs['is_enabled'] ?? false),
            scheduleMode: (string) ($attrs['schedule_mode'] ?? ($definition['schedule']['mode'] ?? 'immediate')),
            cronExpression: isset($attrs['cron_expression'])
                ? (string) $attrs['cron_expression']
                : (isset($definition['schedule']['cron']) ? (string) $definition['schedule']['cron'] : null),
            delaySeconds: (int) ($attrs['delay_seconds'] ?? ($definition['schedule']['delay_seconds'] ?? 0)),
            maxRetries: (int) ($attrs['max_retries'] ?? 0),
            retryDelaySeconds: (int) ($attrs['retry_delay_seconds'] ?? 60),
            definition: $definition,
            changelog: isset($attrs['changelog']) ? (string) $attrs['changelog'] : 'Initial version',
        );
    }
}
