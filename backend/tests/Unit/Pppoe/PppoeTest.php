<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Pppoe;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Pppoe module.
 *
 * @group unit
 * @group Pppoe
 */
class PppoeTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Pppoe\\Services\\'),
            'Expected service class not found.',
        );
    }
}
