<?php

declare(strict_types=1);

namespace SkyFi\Reports\Contracts;

use SkyFi\Reports\DTOs\ReportRequest;

interface ReportQueryBuilderContract
{
    /** @return array{sql:string,count_sql:string,params:array<string,mixed>,columns:array<int,array<string,string>>} */
    public function build(ReportRequest $request, bool $export = false): array;
}
