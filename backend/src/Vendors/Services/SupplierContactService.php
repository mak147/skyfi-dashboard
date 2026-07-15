<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Services;
use SkyFi\Rbac\Contracts\AuditLoggerContract;use SkyFi\Shared\Exceptions\NotFoundException;use SkyFi\Vendors\Contracts\SupplierContactRepositoryContract;use SkyFi\Vendors\Contracts\SupplierRepositoryContract;use SkyFi\Vendors\DomainModels\SupplierContact;use SkyFi\Vendors\DTOs\ContactData;use SkyFi\Vendors\DTOs\ContactListFilters;use SkyFi\Vendors\Validators\ContactValidator;
final class SupplierContactService
{
 public function __construct(private readonly SupplierContactRepositoryContract $repository,private readonly SupplierRepositoryContract $suppliers,private readonly ContactValidator $validator,private readonly AuditLoggerContract $audit){}
 public function list(ContactListFilters $filters):array{return $this->repository->list($filters);}public function get(int $vendorId,int $id):SupplierContact{$contact=$this->repository->find($id)??throw new NotFoundException('Supplier contact not found.');if($contact->vendorId()!==$vendorId)throw new NotFoundException('Supplier contact not found.');return $contact;}
 public function create(int $vendorId,ContactData $data,int $actor,?string $ip=null,?string $agent=null):SupplierContact{$this->supplier($vendorId);$this->validator->validate($data);$item=$this->repository->create($vendorId,$data,$actor);$this->audit->log($actor,'vendors.contact.created','supplier_contact',$item->id(),null,$item->toArray(),$ip,$agent);return $item;}
 public function update(int $vendorId,int $id,ContactData $data,int $actor,?string $ip=null,?string $agent=null):SupplierContact{$old=$this->get($vendorId,$id);$this->validator->validate($data);$item=$this->repository->update($id,$data,$actor);$this->audit->log($actor,'vendors.contact.updated','supplier_contact',$id,$old->toArray(),$item->toArray(),$ip,$agent);return $item;}
 public function delete(int $vendorId,int $id,int $actor,?string $ip=null,?string $agent=null):void{$old=$this->get($vendorId,$id);$this->repository->delete($id,$actor);$this->audit->log($actor,'vendors.contact.archived','supplier_contact',$id,$old->toArray(),null,$ip,$agent);}
 public function designate(int $vendorId,int $id,string $type,int $actor,?string $ip=null,?string $agent=null):SupplierContact{$old=$this->get($vendorId,$id);$item=$this->repository->designate($id,$type,$actor);$this->audit->log($actor,'vendors.contact.'.$type,'supplier_contact',$id,$old->toArray(),$item->toArray(),$ip,$agent);return $item;}
 private function supplier(int $id):void{if($this->suppliers->find($id)===null)throw new NotFoundException('Supplier not found.');}
}
