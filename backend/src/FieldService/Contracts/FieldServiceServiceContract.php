<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Contracts;
interface FieldServiceServiceContract { public function dashboard():array; public function complete(int $id,array $data,int $actor):array; }
