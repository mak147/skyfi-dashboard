<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Workflow;

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test suite for the Workflow module.
 *
 * @group unit
 * @group Workflow
 */
class WorkflowTest extends TestCase
{
    public function testModuleExists(): void
    {
        $this->assertTrue(
            class_exists('SkyFi\\Workflow\\Services\\'),
            'Expected service class not found.',
        );
    }
}
