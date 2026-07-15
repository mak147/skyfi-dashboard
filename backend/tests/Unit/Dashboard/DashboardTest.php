<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Dashboard;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Dashboard module.
 *
 * @group unit
 * @group Dashboard
 */
class DashboardTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Dashboard\\Services\\'),
            'Expected service class not found.',
        );
    }
}
