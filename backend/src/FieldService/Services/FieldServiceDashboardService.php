<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Services;
use SkyFi\FieldService\Contracts\FieldServiceRepositoryContract;
final class FieldServiceDashboardService {public function __construct(private readonly FieldServiceRepositoryContract$repo){}public function dashboard():array{return$this->repo->dashboard();}}
