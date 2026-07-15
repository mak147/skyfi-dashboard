<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Notifications;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Notifications module.
 *
 * @group unit
 * @group Notifications
 */
class NotificationsTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Notifications\\Services\\'),
            'Expected service class not found.',
        );
    }
}
