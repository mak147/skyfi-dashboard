<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

/**
 * Base contract for all external service connectors.
 * Connectors are placeholders — they store configuration and provide a test endpoint,
 * but contain no provider-specific business logic.
 */
interface ConnectorContract
{
    /** The unique connector type identifier (e.g. 'stripe', 'jazzcash'). */
    public function type(): string;

    /** Human-readable connector name. */
    public function name(): string;

    /** Short description. */
    public function description(): string;

    /** Default configuration schema (field names and placeholder values). */
    public function defaultConfig(): array;

    /** The category this connector belongs to (e.g. 'payment', 'messaging', 'mapping'). */
    public function category(): string;

    /**
     * Test the connector connection using the stored config.
     *
     * @param array<string, mixed> $config
     * @return array{success: bool, message: string}
     */
    public function test(array $config): array;
}
