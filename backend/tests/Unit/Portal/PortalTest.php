<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Portal;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Portal module.
 *
 * @group unit
 * @group Portal
 */
class PortalTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Portal\\Services\\'),
            'Expected service class not found.',
        );
    }
}
