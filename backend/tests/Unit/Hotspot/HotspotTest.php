<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Hotspot;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Hotspot module.
 *
 * @group unit
 * @group Hotspot
 */
class HotspotTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Hotspot\\Services\\'),
            'Expected service class not found.',
        );
    }
}
