<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Backup;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Backup module.
 *
 * @group unit
 * @group Backup
 */
class BackupTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Backup\\Services\\'),
            'Expected service class not found.',
        );
    }
}
