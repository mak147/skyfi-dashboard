<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Billing;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Billing module.
 *
 * @group unit
 * @group Billing
 */
class BillingTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Billing\\Services\\'),
            'Expected service class not found.',
        );
    }
}
