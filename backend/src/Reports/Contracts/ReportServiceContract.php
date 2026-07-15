<?php

declare(strict_types=1);

namespace SkyFi\Reports\Contracts;

use SkyFi\Reports\DTOs\ReportRequest;

interface ReportServiceContract
{
    /** @return array<string, mixed> */
    public function generate(ReportRequest $request, bool $export = false): array;
}
