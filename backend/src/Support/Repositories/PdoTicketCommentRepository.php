<?php

declare(strict_types=1);
namespace SkyFi\Support\Repositories;
use PDO;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Support\Contracts\TicketCommentRepositoryContract;
use SkyFi\Support\DomainModels\TicketComment;
final class PdoTicketCommentRepository implements
    TicketCommentRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}
    public function list(int $ticketId): array
    {
        $s = $this->pdo->prepare(
            "SELECT c.*,u.name author_user_name,cu.full_name author_customer_name FROM ticket_comments c LEFT JOIN users u ON u.id=c.author_user_id LEFT JOIN customers cu ON cu.id=c.author_customer_id WHERE c.ticket_id=:id AND c.deleted_at IS NULL ORDER BY c.created_at",
        );
        $s->execute(["id" => $ticketId]);
        return array_map(TicketComment::fromRow(...), $s->fetchAll());
    }
    public function create(
        int $ticketId,
        string $type,
        string $body,
        int $actorId,
        ?int $customerId,
    ): TicketComment {
        $s = $this->pdo->prepare(
            "INSERT INTO ticket_comments(ticket_id,type,body,author_user_id,author_customer_id,created_by) VALUES(:ticket,:type,:body,:user,:customer,:user)",
        );
        $s->execute([
            "ticket" => $ticketId,
            "type" => $type,
            "body" => $body,
            "user" => $actorId,
            "customer" => $customerId,
        ]);
        return $this->find($ticketId, (int) $this->pdo->lastInsertId());
    }
    public function update(
        int $ticketId,
        int $commentId,
        string $body,
        int $actorId,
    ): TicketComment {
        $this->pdo
            ->prepare(
                "UPDATE ticket_comments SET body=:body,is_edited=1,updated_by=:actor WHERE id=:id AND ticket_id=:ticket AND deleted_at IS NULL",
            )
            ->execute([
                "body" => $body,
                "actor" => $actorId,
                "id" => $commentId,
                "ticket" => $ticketId,
            ]);
        return $this->find($ticketId, $commentId);
    }
    public function delete(int $ticketId, int $commentId, int $actorId): void
    {
        $this->pdo
            ->prepare(
                "UPDATE ticket_comments SET deleted_at=UTC_TIMESTAMP(),updated_by=:actor WHERE id=:id AND ticket_id=:ticket",
            )
            ->execute([
                "actor" => $actorId,
                "id" => $commentId,
                "ticket" => $ticketId,
            ]);
    }
    private function find(int $ticketId, int $id): TicketComment
    {
        $s = $this->pdo->prepare(
            "SELECT * FROM ticket_comments WHERE id=:id AND ticket_id=:ticket AND deleted_at IS NULL",
        );
        $s->execute(["id" => $id, "ticket" => $ticketId]);
        $r = $s->fetch();
        if ($r === false) {
            throw new NotFoundException("Ticket comment not found.");
        }
        return TicketComment::fromRow($r);
    }
}
