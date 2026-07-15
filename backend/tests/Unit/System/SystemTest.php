<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\System;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the System module.
 *
 * @group unit
 * @group System
 */
class SystemTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\System\\Services\\'),
            'Expected service class not found.',
        );
    }
}
