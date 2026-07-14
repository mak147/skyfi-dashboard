<?php

declare(strict_types=1);
namespace SkyFi\Support\Contracts;
use SkyFi\Support\DomainModels\SupportTicket;
use SkyFi\Support\DomainModels\TicketComment;
use SkyFi\Support\DTOs\{
    AssignmentData,
    CreateCommentData,
    CreateTicketData,
    EscalationData,
    MergeTicketsData,
    SplitTicketData,
    TicketListFilters,
    UpdateTicketData,
};
interface TicketServiceContract
{
    public function list(TicketListFilters $filters): array;
    public function get(int $id): array;
    public function create(CreateTicketData $data, int $actorId): SupportTicket;
    public function update(
        int $id,
        UpdateTicketData $data,
        int $actorId,
    ): SupportTicket;
    public function delete(int $id, int $actorId): void;
    public function assign(
        int $id,
        AssignmentData $data,
        int $actorId,
    ): SupportTicket;
    public function transition(
        int $id,
        string $status,
        int $actorId,
        ?string $resolution = null,
        ?string $rootCause = null,
    ): SupportTicket;
    public function escalate(
        int $id,
        EscalationData $data,
        int $actorId,
    ): SupportTicket;
    public function merge(
        int $targetId,
        MergeTicketsData $data,
        int $actorId,
    ): SupportTicket;
    public function split(
        int $id,
        SplitTicketData $data,
        int $actorId,
    ): SupportTicket;
    public function comment(
        int $id,
        CreateCommentData $data,
        int $actorId,
    ): TicketComment;
}
