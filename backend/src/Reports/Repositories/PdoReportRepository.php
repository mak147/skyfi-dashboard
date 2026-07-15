<?php

declare(strict_types=1);

namespace SkyFi\Reports\Repositories;

use PDO;
use SkyFi\Reports\Contracts\ReportQueryBuilderContract;
use SkyFi\Reports\Contracts\ReportRepositoryContract;
use SkyFi\Reports\DTOs\ReportRequest;

final class PdoReportRepository implements ReportRepositoryContract
{
    public function __construct(private readonly PDO $pdo, private readonly ReportQueryBuilderContract $builder) {}

    public function run(ReportRequest $request, bool $export = false): array
    {
        $query = $this->builder->build($request, $export);
        $count = $this->pdo->prepare($query['count_sql']);
        foreach ($query['params'] as $key=>$value) if (!in_array($key,['report_limit','report_offset'],true)) $count->bindValue(':'.$key,$value,is_int($value)?PDO::PARAM_INT:PDO::PARAM_STR);
        $count->execute();
        $statement = $this->pdo->prepare($query['sql']);
        foreach ($query['params'] as $key=>$value) $statement->bindValue(':'.$key,$value,is_int($value)?PDO::PARAM_INT:PDO::PARAM_STR);
        $statement->execute();
        return ['rows'=>$statement->fetchAll() ?: [],'total'=>(int)$count->fetchColumn(),'columns'=>$query['columns']];
    }

    public function filterOptions(): array
    {
        $sets = [
            'customers'=>"SELECT id,CONCAT(customer_code,' · ',full_name) label FROM customers WHERE deleted_at IS NULL ORDER BY full_name LIMIT 500",
            'regions'=>"SELECT city id,city label FROM customers WHERE deleted_at IS NULL AND city<>'' GROUP BY city ORDER BY city",
            'pop_sites'=>"SELECT id,name label FROM pop_sites WHERE deleted_at IS NULL ORDER BY name",
            'towers'=>"SELECT id,name label FROM towers WHERE deleted_at IS NULL ORDER BY name",
            'packages'=>"SELECT id,name label FROM packages WHERE deleted_at IS NULL ORDER BY name",
            'technicians'=>"SELECT t.id,CONCAT(u.name,' · ',t.employee_code) label FROM technicians t JOIN users u ON u.id=t.user_id WHERE t.deleted_at IS NULL ORDER BY u.name",
            'suppliers'=>"SELECT id,company_name label FROM vendors WHERE deleted_at IS NULL ORDER BY company_name",
            'warehouses'=>"SELECT id,name label FROM warehouses WHERE deleted_at IS NULL ORDER BY name",
        ];
        $result=[];
        foreach ($sets as $key=>$sql) $result[$key]=$this->pdo->query($sql)->fetchAll() ?: [];
        return $result;
    }
}
