<?php

declare(strict_types=1);
namespace SkyFi\Support\Repositories;

use PDO;
use SkyFi\Support\Contracts\TicketRepositoryContract;
use SkyFi\Support\DomainModels\{SlaPolicy, SupportTicket};
use SkyFi\Support\DTOs\TicketListFilters;

final class PdoTicketRepository implements TicketRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(TicketListFilters $f): array
    {
        $where = ["t.deleted_at IS NULL"];
        $params = [];
        $map = [
            "status" => "t.status",
            "priority" => "t.priority",
            "category_id" => "t.category_id",
            "customer_id" => "t.customer_id",
            "connection_id" => "t.connection_id",
            "assigned_team_id" => "a.team_id",
            "assigned_staff_id" => "a.staff_user_id",
            "router_id" => "t.router_id",
            "network_device_id" => "t.network_device_id",
            "monitoring_alert_id" => "t.monitoring_alert_id",
        ];
        foreach ($map as $key => $column) {
            if (isset($f->filters[$key]) && $f->filters[$key] !== "") {
                $where[] = "$column = :$key";
                $params[$key] = $f->filters[$key];
            }
        }
        if (($f->filters["sla"] ?? "") === "breached") {
            $where[] =
                "(t.response_breached_at IS NOT NULL OR t.resolution_breached_at IS NOT NULL)";
        }
        if (($f->filters["sla"] ?? "") === "overdue") {
            $where[] =
                "(t.status NOT IN ('resolved','closed','cancelled') AND t.resolution_due_at < UTC_TIMESTAMP())";
        }
        if (
            isset($f->filters["search"]) &&
            trim((string) $f->filters["search"]) !== ""
        ) {
            $where[] =
                "(t.ticket_number LIKE :search OR t.subject LIKE :search OR t.description LIKE :search OR c.full_name LIKE :search OR c.customer_code LIKE :search OR cn.connection_number LIKE :search)";
            $params["search"] =
                "%" . trim((string) $f->filters["search"]) . "%";
        }
        if (isset($f->filters["created_from"])) {
            $where[] = "t.created_at >= :created_from";
            $params["created_from"] = $f->filters["created_from"] . " 00:00:00";
        }
        if (isset($f->filters["created_to"])) {
            $where[] = "t.created_at <= :created_to";
            $params["created_to"] = $f->filters["created_to"] . " 23:59:59";
        }
        $join =
            " FROM support_tickets t JOIN customers c ON c.id=t.customer_id LEFT JOIN connections cn ON cn.id=t.connection_id JOIN ticket_categories cat ON cat.id=t.category_id JOIN sla_policies sla ON sla.id=t.sla_policy_id LEFT JOIN ticket_assignments a ON a.ticket_id=t.id AND a.ended_at IS NULL LEFT JOIN support_teams team ON team.id=a.team_id LEFT JOIN users staff ON staff.id=a.staff_user_id";
        $whereSql = " WHERE " . implode(" AND ", $where);
        $count = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT t.id)" . $join . $whereSql,
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $sorts = [
            "created_at" => "t.created_at",
            "updated_at" => "t.updated_at",
            "priority" =>
                'FIELD(t.priority,\'urgent\',\'high\',\'normal\',\'low\')',
            "status" => "t.status",
            "resolution_due_at" => "t.resolution_due_at",
            "ticket_number" => "t.ticket_number",
        ];
        $descending = str_starts_with($f->sort, "-");
        $field = ltrim($f->sort, "-");
        $order =
            ($sorts[$field] ?? $sorts["created_at"]) .
            ($descending ? " DESC" : " ASC") .
            ", t.id DESC";
        $offset = ($f->page - 1) * $f->perPage;
        $sql =
            "SELECT t.*,c.full_name customer_name,c.customer_code,cn.connection_number,cat.name category_name,sla.name sla_name,a.team_id assigned_team_id,team.name assigned_team_name,a.staff_user_id assigned_staff_id,staff.name assigned_staff_name" .
            $join .
            $whereSql .
            " ORDER BY $order LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue("limit", $f->perPage, PDO::PARAM_INT);
        $stmt->bindValue("offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = SupportTicket::fromRow($row);
        }
        return [
            "items" => $items,
            "total" => $total,
            "page" => $f->page,
            "perPage" => $f->perPage,
            "lastPage" => max(1, (int) ceil($total / $f->perPage)),
        ];
    }

    public function find(int $id, bool $forUpdate = false): ?SupportTicket
    {
        $sql =
            "SELECT t.*,c.full_name customer_name,c.customer_code,c.phone customer_phone,c.status customer_status,cn.connection_number,cn.name connection_name,cn.status connection_status,p.name package_name,cat.name category_name,s.name sla_name,s.pause_while_waiting_customer,a.team_id assigned_team_id,team.name assigned_team_name,a.staff_user_id assigned_staff_id,u.name assigned_staff_name FROM support_tickets t JOIN customers c ON c.id=t.customer_id LEFT JOIN connections cn ON cn.id=t.connection_id LEFT JOIN packages p ON p.id=t.package_id JOIN ticket_categories cat ON cat.id=t.category_id JOIN sla_policies s ON s.id=t.sla_policy_id LEFT JOIN ticket_assignments a ON a.ticket_id=t.id AND a.ended_at IS NULL LEFT JOIN support_teams team ON team.id=a.team_id LEFT JOIN users u ON u.id=a.staff_user_id WHERE t.id=:id AND t.deleted_at IS NULL" .
            ($forUpdate ? " FOR UPDATE" : "");
        $st = $this->pdo->prepare($sql);
        $st->execute(["id" => $id]);
        $row = $st->fetch();
        return $row === false ? null : SupportTicket::fromRow($row);
    }

    public function create(
        array $data,
        int $actorId,
        SlaPolicy $policy,
        ?int $parentId = null,
    ): SupportTicket {
        $now = new \DateTimeImmutable("now", new \DateTimeZone("UTC"));
        $data += [
            "connection_id" => null,
            "package_id" => null,
            "pppoe_account_id" => null,
            "hotspot_user_id" => null,
            "router_id" => null,
            "network_device_id" => null,
            "monitoring_alert_id" => null,
        ];
        $stmt = $this->pdo->prepare(
            'INSERT INTO support_tickets (customer_id,connection_id,package_id,pppoe_account_id,hotspot_user_id,router_id,network_device_id,monitoring_alert_id,category_id,sla_policy_id,priority,status,source,subject,description,parent_ticket_id,first_response_due_at,resolution_due_at,created_by) VALUES (:customer_id,:connection_id,:package_id,:pppoe_account_id,:hotspot_user_id,:router_id,:network_device_id,:monitoring_alert_id,:category_id,:sla_policy_id,:priority,\'new\',:source,:subject,:description,:parent_ticket_id,:response_due,:resolution_due,:created_by)',
        );
        $stmt->execute([
            ...array_intersect_key(
                $data,
                array_flip([
                    "customer_id",
                    "connection_id",
                    "package_id",
                    "pppoe_account_id",
                    "hotspot_user_id",
                    "router_id",
                    "network_device_id",
                    "monitoring_alert_id",
                    "category_id",
                    "priority",
                    "source",
                    "subject",
                    "description",
                ]),
            ),
            "sla_policy_id" => $policy->id(),
            "parent_ticket_id" => $parentId,
            "response_due" => $now
                ->modify("+" . $policy->responseMinutes() . " minutes")
                ->format("Y-m-d H:i:s"),
            "resolution_due" => $now
                ->modify("+" . $policy->resolutionMinutes() . " minutes")
                ->format("Y-m-d H:i:s"),
            "created_by" => $actorId,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $number =
            "TKT-" .
            $now->format("Y") .
            "-" .
            str_pad((string) $id, 7, "0", STR_PAD_LEFT);
        $this->pdo
            ->prepare(
                "UPDATE support_tickets SET ticket_number=:number WHERE id=:id",
            )
            ->execute(["number" => $number, "id" => $id]);
        return $this->find($id) ??
            throw new \RuntimeException("Ticket creation failed.");
    }

    public function update(int $id, array $data, int $actorId): SupportTicket
    {
        if ($data === []) {
            return $this->find($id) ??
                throw new \RuntimeException("Ticket not found.");
        }
        $allowed = [
            "customer_id",
            "connection_id",
            "package_id",
            "pppoe_account_id",
            "hotspot_user_id",
            "router_id",
            "network_device_id",
            "monitoring_alert_id",
            "category_id",
            "sla_policy_id",
            "priority",
            "status",
            "subject",
            "description",
            "resolution",
            "root_cause",
            "merged_into_ticket_id",
            "first_response_due_at",
            "first_responded_at",
            "response_breached_at",
            "resolution_breached_at",
            "waiting_started_at",
            "sla_paused_seconds",
            "resolution_due_at",
            "escalation_level",
            "escalated_at",
            "resolved_at",
            "closed_at",
            "closed_by",
        ];
        $data = array_intersect_key($data, array_flip($allowed));
        $sets = [];
        $params = ["id" => $id, "actor" => $actorId];
        foreach ($data as $k => $v) {
            $sets[] = "$k=:$k";
            $params[$k] = $v;
        }
        $sets[] = "updated_by=:actor";
        $this->pdo
            ->prepare(
                "UPDATE support_tickets SET " .
                    implode(",", $sets) .
                    " WHERE id=:id AND deleted_at IS NULL",
            )
            ->execute($params);
        return $this->find($id) ??
            throw new \RuntimeException("Ticket update failed.");
    }
    public function softDelete(int $id, int $actorId): void
    {
        $this->pdo
            ->prepare(
                "UPDATE support_tickets SET deleted_at=UTC_TIMESTAMP(),updated_by=:actor WHERE id=:id AND deleted_at IS NULL",
            )
            ->execute(["id" => $id, "actor" => $actorId]);
    }
    public function findSlaPolicy(int $categoryId, string $priority): ?SlaPolicy
    {
        $s = $this->pdo->prepare(
            "SELECT * FROM sla_policies WHERE priority=:priority AND is_active=1 AND deleted_at IS NULL AND (category_id=:category OR category_id IS NULL) ORDER BY category_id IS NULL ASC,id LIMIT 1",
        );
        $s->execute(["category" => $categoryId, "priority" => $priority]);
        $r = $s->fetch();
        return $r === false ? null : SlaPolicy::fromRow($r);
    }
    public function categories(): array
    {
        return $this->all(
            "SELECT c.*,t.name default_team_name FROM ticket_categories c LEFT JOIN support_teams t ON t.id=c.default_team_id WHERE c.deleted_at IS NULL ORDER BY c.name",
        );
    }
    public function teams(): array
    {
        return $this->all(
            "SELECT t.*,COUNT(m.user_id) member_count FROM support_teams t LEFT JOIN support_team_members m ON m.team_id=t.id WHERE t.deleted_at IS NULL GROUP BY t.id ORDER BY t.name",
        );
    }
    public function slaPolicies(): array
    {
        return $this->all(
            'SELECT s.*,c.name category_name,t.name escalation_team_name FROM sla_policies s LEFT JOIN ticket_categories c ON c.id=s.category_id LEFT JOIN support_teams t ON t.id=s.escalation_team_id WHERE s.deleted_at IS NULL ORDER BY FIELD(s.priority,\'urgent\',\'high\',\'normal\',\'low\'),s.name',
        );
    }

    public function dashboard(): array
    {
        $summary = $this->one(
            "SELECT SUM(status IN ('new','open','assigned','in_progress','waiting_customer','escalated')) open_tickets,SUM(status NOT IN ('resolved','closed','cancelled') AND resolution_due_at<UTC_TIMESTAMP()) overdue_tickets,SUM(response_breached_at IS NOT NULL OR resolution_breached_at IS NOT NULL) sla_breaches,SUM(status='new') unassigned_triage FROM support_tickets WHERE deleted_at IS NULL",
        );
        return [
            "summary" => $summary,
            "by_priority" => $this->group("priority"),
            "by_category" => $this->all(
                "SELECT c.name label,COUNT(*) value FROM support_tickets t JOIN ticket_categories c ON c.id=t.category_id WHERE t.deleted_at IS NULL GROUP BY c.id,c.name ORDER BY value DESC",
            ),
            "by_technician" => $this->all(
                "SELECT COALESCE(u.name,'Unassigned') label,COUNT(*) value FROM support_tickets t LEFT JOIN ticket_assignments a ON a.ticket_id=t.id AND a.ended_at IS NULL LEFT JOIN users u ON u.id=a.staff_user_id WHERE t.deleted_at IS NULL AND t.status NOT IN ('closed','cancelled') GROUP BY u.id,u.name ORDER BY value DESC",
            ),
        ];
    }
    public function slaDashboard(): array
    {
        return [
            "summary" => $this->one(
                "SELECT SUM(first_responded_at IS NULL AND first_response_due_at<UTC_TIMESTAMP()) response_overdue,SUM(status NOT IN ('resolved','closed','cancelled') AND resolution_due_at<UTC_TIMESTAMP()) resolution_overdue,ROUND(AVG(TIMESTAMPDIFF(MINUTE,created_at,first_responded_at)),1) avg_response_minutes,ROUND(AVG(TIMESTAMPDIFF(MINUTE,created_at,resolved_at)),1) avg_resolution_minutes FROM support_tickets WHERE deleted_at IS NULL",
            ),
            "policies" => $this->slaPolicies(),
            "by_priority" => $this->all(
                "SELECT priority label,COUNT(*) total,SUM(response_breached_at IS NOT NULL OR resolution_breached_at IS NOT NULL) breached FROM support_tickets WHERE deleted_at IS NULL GROUP BY priority",
            ),
        ];
    }
    public function processBreaches(?int $actorId = null): int
    {
        $s = $this->pdo->prepare(
            "SELECT t.id,t.first_responded_at,t.response_breached_at,t.resolution_breached_at,t.status,t.escalation_level,s.pause_while_waiting_customer,(DATE_ADD(t.created_at,INTERVAL s.escalation_after_minutes MINUTE)<UTC_TIMESTAMP()) escalation_due FROM support_tickets t JOIN sla_policies s ON s.id=t.sla_policy_id WHERE t.deleted_at IS NULL AND t.status NOT IN ('resolved','closed','cancelled') AND ((t.first_responded_at IS NULL AND t.response_breached_at IS NULL AND t.first_response_due_at<UTC_TIMESTAMP()) OR (t.resolution_breached_at IS NULL AND t.resolution_due_at<UTC_TIMESTAMP() AND (t.status<>'waiting_customer' OR s.pause_while_waiting_customer=0)) OR (t.escalation_level=0 AND t.status<>'waiting_customer' AND DATE_ADD(t.created_at,INTERVAL s.escalation_after_minutes MINUTE)<UTC_TIMESTAMP()))",
        );
        $s->execute();
        $count = 0;
        foreach ($s->fetchAll() as $r) {
            $set = [];
            $events = [];
            if (
                $r["first_responded_at"] === null &&
                $r["response_breached_at"] === null
            ) {
                $set[] = "response_breached_at=UTC_TIMESTAMP()";
                $events[] = "response";
            }
            if (
                $r["resolution_breached_at"] === null &&
                !($r["status"] === "waiting_customer" && (int) $r["pause_while_waiting_customer"] === 1) &&
                in_array(
                    $r["status"],
                    [
                        "new",
                        "open",
                        "assigned",
                        "in_progress",
                        "waiting_customer",
                        "escalated",
                    ],
                    true,
                )
            ) {
                $check = $this->pdo
                    ->query(
                        "SELECT resolution_due_at<UTC_TIMESTAMP() FROM support_tickets WHERE id=" .
                            (int) $r["id"],
                    )
                    ->fetchColumn();
                if ($check) {
                    $set[] = "resolution_breached_at=UTC_TIMESTAMP()";
                    $events[] = "resolution";
                }
            }
            if (
                (int) $r["escalation_level"] === 0 &&
                (int) $r["escalation_due"] === 1
            ) {
                $set[] = "status='escalated'";
                $set[] = "escalation_level=1";
                $set[] = "escalated_at=UTC_TIMESTAMP()";
                $events[] = "automatic_escalation";
            }
            if ($set !== []) {
                $this->pdo->exec(
                    "UPDATE support_tickets SET " .
                        implode(",", $set) .
                        " WHERE id=" .
                        (int) $r["id"],
                );
            }
            foreach ($events as $e) {
                if ($e === "automatic_escalation") {
                    $this->history(
                        (int) $r["id"],
                        "escalated",
                        $actorId,
                        "Ticket automatically escalated by its SLA policy.",
                        $r["status"],
                        "escalated",
                        ["automatic" => true],
                    );
                } else {
                    $this->history(
                        (int) $r["id"],
                        "sla_breached",
                        $actorId,
                        ucfirst($e) . " SLA breached.",
                        null,
                        null,
                        ["sla_type" => $e],
                    );
                }
            }
            $count++;
        }
        return $count;
    }
    public function history(
        int $ticketId,
        string $event,
        ?int $actorId,
        string $description,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?array $metadata = null,
    ): void {
        $s = $this->pdo->prepare(
            "INSERT INTO ticket_history(ticket_id,event_type,actor_user_id,old_status,new_status,description,metadata) VALUES(:ticket,:event,:actor,:old,:new,:description,:metadata)",
        );
        $s->execute([
            "ticket" => $ticketId,
            "event" => $event,
            "actor" => $actorId,
            "old" => $oldStatus,
            "new" => $newStatus,
            "description" => $description,
            "metadata" => $metadata
                ? json_encode($metadata, JSON_THROW_ON_ERROR)
                : null,
        ]);
    }
    public function timeline(int $ticketId): array
    {
        $children =
            "SELECT id FROM support_tickets WHERE merged_into_ticket_id=" .
            (int) $ticketId;
        $history =
            "SELECT id,'history' item_kind,event_type type,description body,actor_user_id author_user_id,NULL author_customer_id,old_status,new_status,metadata,created_at FROM ticket_history WHERE ticket_id=" .
            (int) $ticketId .
            " OR ticket_id IN ($children)";
        $comments =
            "SELECT id,'comment',type,body,author_user_id,author_customer_id,NULL,NULL,NULL,created_at FROM ticket_comments WHERE deleted_at IS NULL AND (ticket_id=" .
            (int) $ticketId .
            " OR ticket_id IN ($children))";
        return $this->all(
            $history . " UNION ALL " . $comments . " ORDER BY created_at,id",
        );
    }
    public function assignments(int $ticketId): array
    {
        return $this->all(
            "SELECT a.*,t.name team_name,u.name staff_name,ab.name assigned_by_name FROM ticket_assignments a LEFT JOIN support_teams t ON t.id=a.team_id LEFT JOIN users u ON u.id=a.staff_user_id JOIN users ab ON ab.id=a.assigned_by WHERE a.ticket_id=" .
                (int) $ticketId .
                " ORDER BY a.assigned_at DESC",
        );
    }
    public function context(int $ticketId): array
    {
        $t = $this->find($ticketId);
        if (!$t) {
            return [];
        }
        $a = $t->toArray();
        $context = [
            "customer" => array_intersect_key(
                $a,
                array_flip([
                    "customer_id",
                    "customer_name",
                    "customer_code",
                    "customer_phone",
                    "customer_status",
                ]),
            ),
            "connection" => array_intersect_key(
                $a,
                array_flip([
                    "connection_id",
                    "connection_number",
                    "connection_name",
                    "connection_status",
                ]),
            ),
            "package" => array_intersect_key(
                $a,
                array_flip(["package_id", "package_name"]),
            ),
        ];
        foreach (
            [
                "pppoe_account_id" => [
                    "pppoe_accounts",
                    "username,status,sync_status,last_connected_at",
                ],
                "hotspot_user_id" => [
                    "hotspot_users",
                    "username,status,sync_status,last_connected_at",
                ],
                "router_id" => [
                    "mikrotik_routers",
                    "name,host,last_connection_status,last_connected_at",
                ],
                "network_device_id" => [
                    "network_devices",
                    "name,device_type,status,ip_address",
                ],
                "monitoring_alert_id" => [
                    "monitoring_alerts",
                    "title,severity,status,triggered_at",
                ],
            ]
            as $key => $spec
        ) {
            if (!empty($a[$key])) {
                $r = $this->one(
                    "SELECT id," .
                        $spec[1] .
                        " FROM " .
                        $spec[0] .
                        " WHERE id=" .
                        (int) $a[$key],
                );
                $context[str_replace("_id", "", $key)] = $r;
            }
        }
        return $context;
    }
    public function lookup(
        string $resource,
        string $search,
        ?int $customerId = null,
    ): array {
        $q = $this->pdo->quote("%" . $search . "%");
        $limit = " LIMIT 30";
        return match ($resource) {
            "customers" => $this->all(
                "SELECT id,customer_code,full_name,phone,status FROM customers WHERE deleted_at IS NULL AND (full_name LIKE $q OR customer_code LIKE $q OR phone LIKE $q) ORDER BY full_name$limit",
            ),
            "connections" => $this->all(
                "SELECT id,connection_number,name,customer_id,package_id,type,status FROM connections WHERE deleted_at IS NULL" .
                    ($customerId
                        ? " AND customer_id=" . (int) $customerId
                        : "") .
                    " AND (name LIKE $q OR connection_number LIKE $q) ORDER BY name$limit",
            ),
            "network" => $this->all(
                "SELECT CONCAT('router:',id) value,name label,'router' type,last_connection_status status FROM mikrotik_routers WHERE deleted_at IS NULL AND name LIKE $q UNION ALL SELECT CONCAT('device:',id),name,'device',status FROM network_devices WHERE deleted_at IS NULL AND name LIKE $q LIMIT 30",
            ),
            "packages" => $this->all("SELECT id,name,status FROM packages WHERE deleted_at IS NULL AND name LIKE $q ORDER BY name$limit"),
            "pppoe" => $this->all("SELECT id,username,customer_id,connection_id,router_id,status FROM pppoe_accounts WHERE deleted_at IS NULL AND username LIKE $q" . ($customerId ? " AND customer_id=" . (int) $customerId : "") . " ORDER BY username$limit"),
            "hotspot" => $this->all("SELECT id,username,customer_id,connection_id,router_id,status FROM hotspot_users WHERE deleted_at IS NULL AND username LIKE $q" . ($customerId ? " AND customer_id=" . (int) $customerId : "") . " ORDER BY username$limit"),
            "routers" => $this->all("SELECT id,name,last_connection_status status FROM mikrotik_routers WHERE deleted_at IS NULL AND name LIKE $q ORDER BY name$limit"),
            "devices" => $this->all("SELECT id,name,device_type,status FROM network_devices WHERE deleted_at IS NULL AND name LIKE $q ORDER BY name$limit"),
            "alerts" => $this->all("SELECT id,title,severity,status FROM monitoring_alerts WHERE title LIKE $q ORDER BY triggered_at DESC$limit"),
            "assignees" => $this->all(
                "SELECT id,name,email FROM users WHERE deleted_at IS NULL AND name LIKE $q ORDER BY name$limit",
            ),
            default => [],
        };
    }
    public function saveConfiguration(
        string $resource,
        ?int $id,
        array $data,
        int $actorId,
    ): array {
        $config = match ($resource) {
            "categories" => [
                "ticket_categories",
                ["name", "slug", "description", "default_team_id", "is_active"],
            ],
            "sla-policies" => [
                "sla_policies",
                [
                    "name",
                    "category_id",
                    "priority",
                    "response_minutes",
                    "resolution_minutes",
                    "escalation_after_minutes",
                    "pause_while_waiting_customer",
                    "escalation_team_id",
                    "is_active",
                ],
            ],
            "teams" => ["support_teams", ["name", "description", "is_active"]],
            default => throw new \InvalidArgumentException(
                "Unsupported support configuration resource.",
            ),
        };
        [$table, $allowed] = $config;
        $values = array_intersect_key($data, array_flip($allowed));
        if (trim((string) ($values["name"] ?? "")) === "") {
            throw new \SkyFi\Shared\Exceptions\ValidationException([
                ["code" => "name_required", "detail" => "Name is required."],
            ]);
        }
        if ($resource === "categories" && empty($values["slug"])) {
            $values["slug"] = strtolower(
                trim(
                    preg_replace(
                        "/[^a-z0-9]+/i",
                        "-",
                        (string) $values["name"],
                    ),
                    "-",
                ),
            );
        }
        if ($resource === "sla-policies") {
            foreach (
                [
                    "priority",
                    "response_minutes",
                    "resolution_minutes",
                    "escalation_after_minutes",
                ]
                as $key
            ) {
                if (!isset($values[$key])) {
                    throw new \SkyFi\Shared\Exceptions\ValidationException([
                        [
                            "code" => "field_required",
                            "detail" =>
                                str_replace("_", " ", ucfirst($key)) .
                                " is required.",
                        ],
                    ]);
                }
            }
            if (
                !in_array(
                    $values["priority"],
                    ["low", "normal", "high", "urgent"],
                    true,
                ) ||
                (int) $values["response_minutes"] < 1 ||
                (int) $values["resolution_minutes"] <
                    (int) $values["response_minutes"]
            ) {
                throw new \SkyFi\Shared\Exceptions\ValidationException([
                    [
                        "code" => "invalid_sla_policy",
                        "detail" =>
                            "SLA priority and time targets are invalid.",
                    ],
                ]);
            }
        }
        if ($id === null) {
            $values["created_by"] = $actorId;
            $cols = array_keys($values);
            $sql =
                "INSERT INTO " .
                $table .
                " (" .
                implode(",", $cols) .
                ") VALUES (:" .
                implode(",:", $cols) .
                ")";
            $this->pdo->prepare($sql)->execute($values);
            $id = (int) $this->pdo->lastInsertId();
        } else {
            $values["updated_by"] = $actorId;
            $sets = [];
            foreach (array_keys($values) as $k) {
                $sets[] = "$k=:$k";
            }
            $values["id"] = $id;
            $this->pdo
                ->prepare(
                    "UPDATE " .
                        $table .
                        " SET " .
                        implode(",", $sets) .
                        " WHERE id=:id AND deleted_at IS NULL",
                )
                ->execute($values);
        }
        return $this->one("SELECT * FROM " . $table . " WHERE id=" . (int) $id);
    }
    public function deleteConfiguration(
        string $resource,
        int $id,
        int $actorId,
    ): void {
        $table = match ($resource) {
            "categories" => "ticket_categories",
            "sla-policies" => "sla_policies",
            "teams" => "support_teams",
            default => throw new \InvalidArgumentException(
                "Unsupported support configuration resource.",
            ),
        };
        $this->pdo
            ->prepare(
                "UPDATE {$table} SET deleted_at=UTC_TIMESTAMP(),updated_by=:actor WHERE id=:id AND deleted_at IS NULL",
            )
            ->execute(["actor" => $actorId, "id" => $id]);
    }
    public function validateContext(array $data): array
    {
        $errors = [];
        $customer = (int) ($data["customer_id"] ?? 0);
        $exists = function (string $table, int $id, string $extra = "") use (
            &$errors,
        ): bool {
            $s = $this->pdo->prepare(
                "SELECT 1 FROM {$table} WHERE id=:id {$extra}",
            );
            $s->execute(["id" => $id]);
            return (bool) $s->fetchColumn();
        };
        if (
            $customer < 1 ||
            !$exists("customers", $customer, "AND deleted_at IS NULL")
        ) {
            $errors[] = [
                "code" => "invalid_customer",
                "detail" => "The selected customer does not exist.",
                "source" => ["pointer" => "/data/attributes/customer_id"],
            ];
        }
        foreach (
            [
                "connection_id" => ["connections", "connection"],
                "package_id" => ["packages", "package"],
                "pppoe_account_id" => ["pppoe_accounts", "PPPoE account"],
                "hotspot_user_id" => ["hotspot_users", "Hotspot user"],
                "router_id" => ["mikrotik_routers", "router"],
                "network_device_id" => ["network_devices", "network device"],
                "monitoring_alert_id" => [
                    "monitoring_alerts",
                    "monitoring alert",
                ],
                "category_id" => ["ticket_categories", "category"],
            ]
            as $key => $meta
        ) {
            if (
                !empty($data[$key]) &&
                !$exists(
                    $meta[0],
                    (int) $data[$key],
                    in_array($meta[0], ["monitoring_alerts"], true)
                        ? ""
                        : "AND deleted_at IS NULL",
                )
            ) {
                $errors[] = [
                    "code" => "invalid_reference",
                    "detail" => "The selected " . $meta[1] . " does not exist.",
                    "source" => ["pointer" => "/data/attributes/" . $key],
                ];
            }
        }
        if (!empty($data["connection_id"])) {
            $s = $this->pdo->prepare(
                "SELECT customer_id,package_id FROM connections WHERE id=:id",
            );
            $s->execute(["id" => $data["connection_id"]]);
            $r = $s->fetch();
            if ($r && $customer > 0 && (int) $r["customer_id"] !== $customer) {
                $errors[] = [
                    "code" => "context_mismatch",
                    "detail" =>
                        "The connection does not belong to the selected customer.",
                    "source" => ["pointer" => "/data/attributes/connection_id"],
                ];
            }
            if (
                $r &&
                !empty($data["package_id"]) &&
                (int) $r["package_id"] !== (int) $data["package_id"]
            ) {
                $errors[] = [
                    "code" => "context_mismatch",
                    "detail" =>
                        "The package does not match the selected connection.",
                    "source" => ["pointer" => "/data/attributes/package_id"],
                ];
            }
        }
        foreach (
            [
                "pppoe_account_id" => "pppoe_accounts",
                "hotspot_user_id" => "hotspot_users",
            ]
            as $key => $table
        ) {
            if (!empty($data[$key])) {
                $s = $this->pdo->prepare(
                    "SELECT customer_id,connection_id,router_id FROM {$table} WHERE id=:id",
                );
                $s->execute(["id" => $data[$key]]);
                $r = $s->fetch();
                if (
                    $r &&
                    $customer > 0 &&
                    $r["customer_id"] !== null &&
                    (int) $r["customer_id"] !== $customer
                ) {
                    $errors[] = [
                        "code" => "context_mismatch",
                        "detail" =>
                            "The selected subscriber account does not belong to this customer.",
                        "source" => ["pointer" => "/data/attributes/" . $key],
                    ];
                }
                if (
                    $r &&
                    !empty($data["connection_id"]) &&
                    $r["connection_id"] !== null &&
                    (int) $r["connection_id"] !== (int) $data["connection_id"]
                ) {
                    $errors[] = [
                        "code" => "context_mismatch",
                        "detail" =>
                            "The selected subscriber account does not belong to this connection.",
                        "source" => ["pointer" => "/data/attributes/" . $key],
                    ];
                }
                if (
                    $r &&
                    !empty($data["router_id"]) &&
                    (int) $r["router_id"] !== (int) $data["router_id"]
                ) {
                    $errors[] = [
                        "code" => "context_mismatch",
                        "detail" =>
                            "The selected subscriber account does not belong to this router.",
                        "source" => ["pointer" => "/data/attributes/" . $key],
                    ];
                }
            }
        }
        return $errors;
    }
    public function transaction(callable $callback): mixed
    {
        $nested = $this->pdo->inTransaction();
        if (!$nested) {
            $this->pdo->beginTransaction();
        }
        try {
            $r = $callback();
            if (!$nested) {
                $this->pdo->commit();
            }
            return $r;
        } catch (\Throwable $e) {
            if (!$nested && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    /** @return array<int,array<string,mixed>> */ private function all(
        string $sql,
    ): array {
        return $this->pdo->query($sql)->fetchAll();
    }
    /** @return array<string,mixed> */ private function one(string $sql): array
    {
        $r = $this->pdo->query($sql)->fetch();
        return $r === false ? [] : $r;
    }
    /** @return array<int,array<string,mixed>> */ private function group(
        string $field,
    ): array {
        return $this->all(
            "SELECT $field label,COUNT(*) value FROM support_tickets WHERE deleted_at IS NULL GROUP BY $field ORDER BY value DESC",
        );
    }
}
