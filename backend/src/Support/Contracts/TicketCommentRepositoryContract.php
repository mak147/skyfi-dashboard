<?php

declare(strict_types=1);
namespace SkyFi\Support\Contracts;
use SkyFi\Support\DomainModels\TicketComment;
interface TicketCommentRepositoryContract
{
    /** @return array<int,TicketComment> */ public function list(
        int $ticketId,
    ): array;
    public function create(
        int $ticketId,
        string $type,
        string $body,
        int $actorId,
        ?int $customerId,
    ): TicketComment;
    public function update(
        int $ticketId,
        int $commentId,
        string $body,
        int $actorId,
    ): TicketComment;
    public function delete(int $ticketId, int $commentId, int $actorId): void;
}
