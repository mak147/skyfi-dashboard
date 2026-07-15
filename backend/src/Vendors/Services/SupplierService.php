<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Services;

use PDO;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\Contracts\SupplierContactRepositoryContract;
use SkyFi\Vendors\Contracts\SupplierRepositoryContract;
use SkyFi\Vendors\DomainModels\Supplier;
use SkyFi\Vendors\DTOs\SupplierData;
use SkyFi\Vendors\DTOs\SupplierListFilters;
use SkyFi\Vendors\Validators\SupplierValidator;

final class SupplierService
{
    public function __construct(private readonly SupplierRepositoryContract $repository,private readonly SupplierContactRepositoryContract $contacts,private readonly SupplierValidator $validator,private readonly AuditLoggerContract $audit,private readonly PDO $pdo){}
    public function list(SupplierListFilters $filters):array{return $this->repository->list($filters);}
    public function get(int $id):Supplier{return $this->repository->find($id)??throw new NotFoundException('Supplier not found.');}
    public function create(SupplierData $data,int $actorId,?string $ip=null,?string $agent=null):Supplier
    {
        $this->validator->validate($data);$this->ensureUnique($data);$this->validateCategories($data->categoryIds);
        $this->pdo->beginTransaction();try{$supplier=$this->repository->create($data,$actorId);$this->repository->syncCategories($supplier->id(),$data->categoryIds,$actorId);if($data->contactPerson!==null)$this->contacts->upsertPrimary($supplier->id(),$data->contactPerson,$data->phone,$data->email,$actorId);$this->pdo->commit();}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
        $supplier=$this->get($supplier->id());$this->audit->log($actorId,'vendors.supplier.created','supplier',$supplier->id(),null,$supplier->toArray(),$ip,$agent);return $supplier;
    }
    public function update(int $id,SupplierData $data,int $actorId,?string $ip=null,?string $agent=null):Supplier
    {
        $old=$this->get($id);if($old->status()==='archived')throw new ValidationException([['code'=>'supplier_archived','detail'=>'Activate the supplier before editing it.']]);$this->validator->validate($data);$this->ensureUnique($data,$id);$this->validateCategories($data->categoryIds);
        $this->pdo->beginTransaction();try{$supplier=$this->repository->update($id,$data,$actorId);$this->repository->syncCategories($id,$data->categoryIds,$actorId);if($data->contactPerson!==null)$this->contacts->upsertPrimary($id,$data->contactPerson,$data->phone,$data->email,$actorId);$this->pdo->commit();}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
        $supplier=$this->get($id);$this->audit->log($actorId,'vendors.supplier.updated','supplier',$id,$old->toArray(),$supplier->toArray(),$ip,$agent);return $supplier;
    }
    public function archive(int $id,int $actorId,?string $ip=null,?string $agent=null):Supplier{$old=$this->get($id);if($old->status()==='archived')return $old;$supplier=$this->repository->archive($id,$actorId);$this->audit->log($actorId,'vendors.supplier.archived','supplier',$id,$old->toArray(),$supplier->toArray(),$ip,$agent);return $supplier;}
    public function activate(int $id,int $actorId,?string $ip=null,?string $agent=null):Supplier{$old=$this->get($id);$supplier=$this->repository->activate($id,$actorId);$this->audit->log($actorId,'vendors.supplier.activated','supplier',$id,$old->toArray(),$supplier->toArray(),$ip,$agent);return $supplier;}
    public function changeStatus(int $id,string $status,int $actorId,?string $ip=null,?string $agent=null):Supplier{if(!in_array($status,['active','inactive','on_hold'],true))throw new ValidationException([['code'=>'invalid_status','detail'=>'Status must be active, inactive, or on hold.','source'=>['pointer'=>'/data/attributes/status']]]);$old=$this->get($id);$supplier=$this->repository->updateStatus($id,$status,$actorId);$this->audit->log($actorId,'vendors.supplier.status_changed','supplier',$id,$old->toArray(),$supplier->toArray(),$ip,$agent);return $supplier;}
    public function categories(bool $activeOnly=false):array{return $this->repository->categories($activeOnly);}
    public function createCategory(array $data,int $actorId,?string $ip=null,?string $agent=null):array{$this->validator->validateCategory($data);$this->ensureUniqueCategory($data);$category=$this->repository->createCategory($data,$actorId);$this->audit->log($actorId,'vendors.category.created','supplier_category',(int)$category['id'],null,$category,$ip,$agent);return $category;}
    public function updateCategory(int $id,array $data,int $actorId,?string $ip=null,?string $agent=null):array{$this->validator->validateCategory($data);$this->ensureUniqueCategory($data,$id);$category=$this->repository->updateCategory($id,$data,$actorId);if($category===[])throw new NotFoundException('Supplier category not found.');$this->audit->log($actorId,'vendors.category.updated','supplier_category',$id,null,$category,$ip,$agent);return $category;}
    public function deleteCategory(int $id,int $actorId,?string $ip=null,?string $agent=null):void{$this->repository->deleteCategory($id,$actorId);$this->audit->log($actorId,'vendors.category.archived','supplier_category',$id,null,null,$ip,$agent);}
    public function purchaseOrders(int $id):array{$this->get($id);return $this->repository->purchaseOrders($id);}
    public function products(int $id):array{$this->get($id);return $this->repository->products($id);}
    public function financialReferences(int $id):array{$this->get($id);return $this->repository->financialReferences($id);}
    private function ensureUnique(SupplierData $data,?int $exceptId=null):void{$errors=[];if($this->repository->existsByCode($data->supplierCode,$exceptId))$errors[]=['code'=>'duplicate_supplier_code','detail'=>'Supplier code is already in use.','source'=>['pointer'=>'/data/attributes/supplier_code']];if($this->repository->existsByName($data->companyName,$exceptId))$errors[]=['code'=>'duplicate_company_name','detail'=>'A supplier with this company name already exists.','source'=>['pointer'=>'/data/attributes/company_name']];if($errors!==[])throw new ValidationException($errors);}
    /** @param array<string,mixed> $data */
    private function ensureUniqueCategory(array $data,?int $exceptId=null):void{foreach($this->repository->categories() as $category){if($exceptId!==null&&(int)$category['id']===$exceptId)continue;if(strcasecmp((string)$category['code'],trim((string)$data['code']))===0||strcasecmp((string)$category['name'],trim((string)$data['name']))===0)throw new ValidationException([['code'=>'duplicate_supplier_category','detail'=>'Supplier category code and name must be unique.']]);}}
    /** @param array<int,int> $categoryIds */
    private function validateCategories(array $categoryIds):void{if($categoryIds===[])return;$valid=array_map(static fn(array $category):int=>(int)$category['id'],$this->repository->categories());$invalid=array_diff($categoryIds,$valid);if($invalid!==[])throw new ValidationException([['code'=>'invalid_supplier_category','detail'=>'One or more selected supplier categories do not exist.','source'=>['pointer'=>'/data/attributes/category_ids']]]);}
}
