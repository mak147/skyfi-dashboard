<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Reports;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Reports module.
 *
 * @group unit
 * @group Reports
 */
class ReportsTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Reports\\Services\\'),
            'Expected service class not found.',
        );
    }
}
