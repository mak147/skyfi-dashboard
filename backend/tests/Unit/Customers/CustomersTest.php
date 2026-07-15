<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Customers;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Customers module.
 *
 * @group unit
 * @group Customers
 */
class CustomersTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Customers\\Services\\'),
            'Expected service class not found.',
        );
    }
}
