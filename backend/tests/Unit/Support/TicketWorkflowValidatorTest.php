<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Support\Validators\TicketWorkflowValidator;

final class TicketWorkflowValidatorTest extends TestCase
{
    public function testAllowsNormalResolutionWorkflow(): void
    {
        $validator = new TicketWorkflowValidator();
        $validator->validate(
            "in_progress",
            "resolved",
            "Subscriber session restored.",
        );
        self::assertTrue(true);
    }

    public function testRejectsClosureWithoutResolution(): void
    {
        $this->expectException(ValidationException::class);
        (new TicketWorkflowValidator())->validate('resolved', 'closed', '');
    }

    public function testRejectsInvalidTransition(): void
    {
        $this->expectException(ValidationException::class);
        (new TicketWorkflowValidator())->validate('new', 'closed', 'Resolved.');
    }
}
