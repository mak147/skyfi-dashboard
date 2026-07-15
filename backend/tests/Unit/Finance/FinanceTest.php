<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Finance;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Finance module.
 *
 * @group unit
 * @group Finance
 */
class FinanceTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Finance\\Services\\'),
            'Expected service class not found.',
        );
    }
}
