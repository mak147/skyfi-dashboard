<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Contracts;
use SkyFi\FieldService\DTOs\FieldServiceListFilters;
interface FieldServiceRepositoryContract
{
    public function listRequests(FieldServiceListFilters $f):array; public function findRequest(int $id):?array; public function findRequestByConnection(int $id):?array; public function createRequest(array $d,int $actor):array; public function updateRequest(int $id,array $d,int $actor):array; public function deleteRequest(int $id,int $actor):void;
    public function listOrders(FieldServiceListFilters $f):array; public function findOrder(int $id,bool $lock=false):?array; public function createOrder(array $d,int $actor):array; public function updateOrder(int $id,array $d,int $actor):array; public function deleteOrder(int $id,int $actor):void;
    public function listTechnicians(FieldServiceListFilters $f):array; public function findTechnician(int $id):?array; public function createTechnician(array $d,int $actor):array; public function updateTechnician(int $id,array $d,int $actor):array; public function deleteTechnician(int $id,int $actor):void;
    public function hasScheduleConflict(int $technicianId,string $start,string $end,?int $except=null):bool; public function schedule(array $filters):array; public function dashboard():array;
    public function materials(int $workOrderId):array; public function saveMaterial(int $workOrderId,?int $id,array $d,int $actor):array; public function deleteMaterial(int $workOrderId,int $id,int $actor):void;
    public function visits(int $workOrderId):array; public function createVisit(int $workOrderId,array $d,int $actor):array; public function updateVisit(int $workOrderId,int $id,array $d,int $actor):array;
    public function logs(int $workOrderId):array; public function saveLog(int $workOrderId,?int $id,array $d,int $actor):array; public function deleteLog(int $workOrderId,int $id,int $actor):void;
    public function timeline(int $workOrderId):array; public function history(int $id,string $event,int $actor,string $description,?string $old=null,?string $new=null,?array $metadata=null):void;
    public function technicianRelation(int $technicianId,string $resource):array; public function saveTechnicianRelation(int $technicianId,string $resource,array $d,int $actor):array; public function updateTechnicianRelation(int $technicianId,string $resource,int $id,array $d,int $actor):array; public function deleteTechnicianRelation(int $technicianId,string $resource,int $id,int $actor):void;
    public function lookup(string $resource,string $search):array; public function customerContext(int $customerId):?array; public function transaction(callable $callback):mixed;
}
