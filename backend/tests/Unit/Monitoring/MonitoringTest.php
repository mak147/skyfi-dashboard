<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Monitoring;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Monitoring module.
 *
 * @group unit
 * @group Monitoring
 */
class MonitoringTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Monitoring\\Services\\'),
            'Expected service class not found.',
        );
    }
}
