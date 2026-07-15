<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Integration module.
 *
 * @group unit
 * @group Integration
 */
class IntegrationTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Integration\\Services\\'),
            'Expected service class not found.',
        );
    }
}
