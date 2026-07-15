<?php

declare(strict_types=1);

namespace SkyFi\Reports\Validators;

use SkyFi\Reports\DTOs\ReportRequest;
use SkyFi\Reports\Services\ReportCatalog;
use SkyFi\Shared\Exceptions\ValidationException;

final class ReportRequestValidator
{
    public function __construct(private readonly ReportCatalog $catalog) {}

    public function validate(ReportRequest $request): void
    {
        $errors = [];
        if ($request->reportKey === '') $errors[] = ['code'=>'report_key_required','detail'=>'A report key is required.','source'=>['pointer'=>'/report_key']];
        else $this->catalog->get($request->reportKey);
        $from = $request->filters['date_from'] ?? null;
        $to = $request->filters['date_to'] ?? null;
        if ($from !== null && !$this->date((string)$from)) $errors[] = ['code'=>'invalid_date_from','detail'=>'Date from must use YYYY-MM-DD.'];
        if ($to !== null && !$this->date((string)$to)) $errors[] = ['code'=>'invalid_date_to','detail'=>'Date to must use YYYY-MM-DD.'];
        if ($from && $to && (string)$from > (string)$to) $errors[] = ['code'=>'invalid_date_range','detail'=>'Date from cannot follow date to.'];
        foreach (['customer_id','pop_site_id','tower_id','package_id','technician_id','supplier_id','warehouse_id'] as $key) {
            if (isset($request->filters[$key]) && (!is_numeric($request->filters[$key]) || (int)$request->filters[$key] < 1)) $errors[] = ['code'=>'invalid_filter','detail'=>"{$key} must be a positive identifier."];
        }
        if ($errors !== []) throw new ValidationException($errors);
    }

    private function date(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
