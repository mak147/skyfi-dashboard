<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Services;
use SkyFi\Rbac\Contracts\AuditLoggerContract;use SkyFi\Shared\Exceptions\NotFoundException;use SkyFi\Shared\Exceptions\ValidationException;use SkyFi\Vendors\Contracts\SupplierContractRepositoryContract;use SkyFi\Vendors\Contracts\SupplierRepositoryContract;use SkyFi\Vendors\DomainModels\SupplierContract;use SkyFi\Vendors\DTOs\ContractData;use SkyFi\Vendors\DTOs\ContractListFilters;use SkyFi\Vendors\Validators\ContractValidator;
final class SupplierContractService
{
 public function __construct(private readonly SupplierContractRepositoryContract $repository,private readonly SupplierRepositoryContract $suppliers,private readonly ContractValidator $validator,private readonly AuditLoggerContract $audit){}
 public function list(ContractListFilters $filters):array{return $this->repository->list($filters);}public function get(int $vendorId,int $id):SupplierContract{$item=$this->repository->find($id)??throw new NotFoundException('Supplier contract not found.');if($item->vendorId()!==$vendorId)throw new NotFoundException('Supplier contract not found.');return $item;}
 public function create(int $vendorId,ContractData $data,int $actor,?string $ip=null,?string $agent=null):SupplierContract{$this->supplier($vendorId);$this->validator->validate($data);$this->unique($data->contractNumber);$item=$this->repository->create($vendorId,$data,$actor);$this->audit->log($actor,'vendors.contract.created','supplier_contract',$item->id(),null,$item->toArray(),$ip,$agent);return $item;}
 public function update(int $vendorId,int $id,ContractData $data,int $actor,?string $ip=null,?string $agent=null):SupplierContract{$old=$this->get($vendorId,$id);$this->validator->validate($data);$this->unique($data->contractNumber,$id);$item=$this->repository->update($id,$data,$actor);$this->audit->log($actor,'vendors.contract.updated','supplier_contract',$id,$old->toArray(),$item->toArray(),$ip,$agent);return $item;}
 public function delete(int $vendorId,int $id,int $actor,?string $ip=null,?string $agent=null):void{$old=$this->get($vendorId,$id);$this->repository->delete($id,$actor);$this->audit->log($actor,'vendors.contract.terminated','supplier_contract',$id,$old->toArray(),null,$ip,$agent);}
 private function supplier(int $id):void{if($this->suppliers->find($id)===null)throw new NotFoundException('Supplier not found.');}private function unique(string $number,?int $except=null):void{if($this->repository->numberExists($number,$except))throw new ValidationException([['code'=>'duplicate_contract_number','detail'=>'Contract number is already in use.','source'=>['pointer'=>'/data/attributes/contract_number']]]);}
}
