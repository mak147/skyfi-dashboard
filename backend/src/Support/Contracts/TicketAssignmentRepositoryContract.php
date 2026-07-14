<?php

declare(strict_types=1);
namespace SkyFi\Support\Contracts;
use SkyFi\Support\DomainModels\TicketAssignment;
interface TicketAssignmentRepositoryContract
{
    public function assign(
        int $ticketId,
        ?int $teamId,
        ?int $staffId,
        int $actorId,
        ?string $reason,
    ): TicketAssignment;
}
