<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Audit;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Audit module.
 *
 * @group unit
 * @group Audit
 */
class AuditTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Audit\\Services\\'),
            'Expected service class not found.',
        );
    }
}
