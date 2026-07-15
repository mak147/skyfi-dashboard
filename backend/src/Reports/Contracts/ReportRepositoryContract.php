<?php

declare(strict_types=1);

namespace SkyFi\Reports\Contracts;

use SkyFi\Reports\DTOs\ReportRequest;

interface ReportRepositoryContract
{
    /** @return array{rows:array<int,array<string,mixed>>,total:int,columns:array<int,array<string,string>>} */
    public function run(ReportRequest $request, bool $export = false): array;
    /** @return array<string, array<int, array<string, mixed>>> */
    public function filterOptions(): array;
}
