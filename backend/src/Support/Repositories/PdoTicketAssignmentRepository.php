<?php

declare(strict_types=1);
namespace SkyFi\Support\Repositories;
use PDO;
use SkyFi\Support\Contracts\TicketAssignmentRepositoryContract;
use SkyFi\Support\DomainModels\TicketAssignment;
final class PdoTicketAssignmentRepository implements
    TicketAssignmentRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}
    public function assign(
        int $ticketId,
        ?int $teamId,
        ?int $staffId,
        int $actorId,
        ?string $reason,
    ): TicketAssignment {
        $this->pdo
            ->prepare(
                "UPDATE ticket_assignments SET ended_at=UTC_TIMESTAMP(),ended_by=:actor WHERE ticket_id=:ticket AND ended_at IS NULL",
            )
            ->execute(["actor" => $actorId, "ticket" => $ticketId]);
        $s = $this->pdo->prepare(
            "INSERT INTO ticket_assignments(ticket_id,team_id,staff_user_id,assigned_by,assignment_reason,assigned_at) VALUES(:ticket,:team,:staff,:actor,:reason,UTC_TIMESTAMP())",
        );
        $s->execute([
            "ticket" => $ticketId,
            "team" => $teamId,
            "staff" => $staffId,
            "actor" => $actorId,
            "reason" => $reason,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $q = $this->pdo->query(
            "SELECT * FROM ticket_assignments WHERE id=" . $id,
        );
        return TicketAssignment::fromRow($q->fetch());
    }
}
