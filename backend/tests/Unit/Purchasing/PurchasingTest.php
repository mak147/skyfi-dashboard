<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Purchasing;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Purchasing module.
 *
 * @group unit
 * @group Purchasing
 */
class PurchasingTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Purchasing\\Services\\'),
            'Expected service class not found.',
        );
    }
}
