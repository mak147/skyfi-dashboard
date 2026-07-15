<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Connections;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Connections module.
 *
 * @group unit
 * @group Connections
 */
class ConnectionsTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Connections\\Services\\'),
            'Expected service class not found.',
        );
    }
}
