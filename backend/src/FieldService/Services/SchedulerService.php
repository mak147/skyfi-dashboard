<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Services;
use SkyFi\FieldService\Contracts\FieldServiceRepositoryContract;
final class SchedulerService {public function __construct(private readonly FieldServiceRepositoryContract$repo){} public function schedule(array$q):array{return$this->repo->schedule($q);} public function unscheduled():array{return$this->repo->listOrders(new \SkyFi\FieldService\DTOs\FieldServiceListFilters(1,100,['status'=>'pending'],'priority'));}}
