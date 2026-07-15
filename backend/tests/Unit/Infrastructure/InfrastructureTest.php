<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Infrastructure module.
 *
 * @group unit
 * @group Infrastructure
 */
class InfrastructureTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Infrastructure\\Services\\'),
            'Expected service class not found.',
        );
    }
}
