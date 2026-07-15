<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\SupplierContactRepositoryContract;
use SkyFi\Vendors\DomainModels\SupplierContact;
use SkyFi\Vendors\DTOs\ContactData;
use SkyFi\Vendors\DTOs\ContactListFilters;

final class PdoSupplierContactRepository implements SupplierContactRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(ContactListFilters $filters): array
    {
        $where=['c.deleted_at IS NULL','v.deleted_at IS NULL'];$params=[];
        if($filters->search!==null){$where[]='(c.name LIKE :search_name OR c.email LIKE :search_email OR c.phone LIKE :search_phone OR v.name LIKE :search_vendor)';$like='%'.$filters->search.'%';$params+=['search_name'=>$like,'search_email'=>$like,'search_phone'=>$like,'search_vendor'=>$like];}
        if($filters->vendorId!==null){$where[]='c.vendor_id=:vendor';$params['vendor']=$filters->vendorId;}
        if($filters->department!==null){$where[]='c.department=:department';$params['department']=$filters->department;}
        if($filters->isPrimary!==null){$where[]='c.is_primary=:primary';$params['primary']=$filters->isPrimary?1:0;}
        if($filters->isEmergency!==null){$where[]='c.is_emergency=:emergency';$params['emergency']=$filters->isEmergency?1:0;}
        $whereSql=implode(' AND ',$where);$count=$this->pdo->prepare("SELECT COUNT(*) FROM supplier_contacts c JOIN vendors v ON v.id=c.vendor_id WHERE {$whereSql}");$count->execute($params);$total=(int)$count->fetchColumn();$offset=($filters->page-1)*$filters->perPage;
        $statement=$this->pdo->prepare("SELECT c.*,v.name AS supplier_name,v.code AS supplier_code FROM supplier_contacts c JOIN vendors v ON v.id=c.vendor_id WHERE {$whereSql} ORDER BY c.is_primary DESC,c.is_emergency DESC,c.name LIMIT {$filters->perPage} OFFSET {$offset}");$statement->execute($params);
        return ['items'=>array_map(static fn(array $row):SupplierContact=>SupplierContact::fromRow($row),$statement->fetchAll(PDO::FETCH_ASSOC)),'total'=>$total,'page'=>$filters->page,'perPage'=>$filters->perPage,'lastPage'=>max(1,(int)ceil($total/$filters->perPage))];
    }

    public function find(int $id): ?SupplierContact
    {
        $statement=$this->pdo->prepare('SELECT c.*,v.name AS supplier_name,v.code AS supplier_code FROM supplier_contacts c JOIN vendors v ON v.id=c.vendor_id WHERE c.id=? AND c.deleted_at IS NULL');$statement->execute([$id]);$row=$statement->fetch(PDO::FETCH_ASSOC);return $row?SupplierContact::fromRow($row):null;
    }

    public function create(int $vendorId,ContactData $data,int $actorId):SupplierContact
    {
        return $this->transaction(function()use($vendorId,$data,$actorId){if($data->isPrimary)$this->clearFlag($vendorId,'is_primary',$actorId);if($data->isEmergency)$this->clearFlag($vendorId,'is_emergency',$actorId);$this->pdo->prepare('INSERT INTO supplier_contacts (vendor_id,name,department,job_title,phone,email,is_primary,is_emergency,notes,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$vendorId,$data->name,$data->department,$data->jobTitle,$data->phone,$data->email,$data->isPrimary?1:0,$data->isEmergency?1:0,$data->notes,$actorId,$actorId]);$id=(int)$this->pdo->lastInsertId();if($data->isPrimary)$this->syncLegacy($vendorId,$data->name,$data->phone,$data->email,$actorId);return $this->find($id)??throw new \RuntimeException('Contact could not be loaded.');});
    }

    public function update(int $id,ContactData $data,int $actorId):SupplierContact
    {
        $existing=$this->find($id)??throw new \RuntimeException('Contact not found.');$vendorId=$existing->vendorId();$wasPrimary=(bool)($existing->toArray()['is_primary']??false);
        return $this->transaction(function()use($id,$vendorId,$data,$actorId,$wasPrimary){if($data->isPrimary)$this->clearFlag($vendorId,'is_primary',$actorId,$id);if($data->isEmergency)$this->clearFlag($vendorId,'is_emergency',$actorId,$id);$this->pdo->prepare('UPDATE supplier_contacts SET name=?,department=?,job_title=?,phone=?,email=?,is_primary=?,is_emergency=?,notes=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND deleted_at IS NULL')->execute([$data->name,$data->department,$data->jobTitle,$data->phone,$data->email,$data->isPrimary?1:0,$data->isEmergency?1:0,$data->notes,$actorId,$id]);if($data->isPrimary)$this->syncLegacy($vendorId,$data->name,$data->phone,$data->email,$actorId);elseif($wasPrimary)$this->syncLegacy($vendorId,null,null,null,$actorId);return $this->find($id)??throw new \RuntimeException('Contact could not be loaded.');});
    }

    public function delete(int $id,int $actorId):void
    {
        $contact=$this->find($id);if($contact===null)return;$row=$contact->toArray();$this->pdo->prepare('UPDATE supplier_contacts SET deleted_at=CURRENT_TIMESTAMP,updated_by=? WHERE id=?')->execute([$actorId,$id]);if((int)$row['is_primary']===1)$this->syncLegacy($contact->vendorId(),null,null,null,$actorId);
    }

    public function designate(int $id,string $type,int $actorId):SupplierContact
    {
        $contact=$this->find($id)??throw new \RuntimeException('Contact not found.');$column=$type==='emergency'?'is_emergency':'is_primary';
        return $this->transaction(function()use($id,$contact,$column,$actorId){$this->clearFlag($contact->vendorId(),$column,$actorId,$id);$this->pdo->prepare("UPDATE supplier_contacts SET {$column}=1,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$actorId,$id]);$updated=$this->find($id)??throw new \RuntimeException('Contact could not be loaded.');if($column==='is_primary'){$row=$updated->toArray();$this->syncLegacy($updated->vendorId(),(string)$row['name'],$row['phone']?:null,$row['email']?:null,$actorId);}return $updated;});
    }

    public function upsertPrimary(int $vendorId,string $name,?string $phone,?string $email,int $actorId):SupplierContact
    {
        $statement=$this->pdo->prepare('SELECT id FROM supplier_contacts WHERE vendor_id=? AND is_primary=1 AND deleted_at IS NULL LIMIT 1');$statement->execute([$vendorId]);$id=$statement->fetchColumn();if($id){$existing=$this->find((int)$id);$row=$existing?->toArray()??[];$data=new ContactData($name,$row['department']??null,$row['job_title']??null,$phone,$email,true,(bool)($row['is_emergency']??false),$row['notes']??null);return $this->update((int)$id,$data,$actorId);}$data=new ContactData($name,null,null,$phone,$email,true,false,null);return $this->create($vendorId,$data,$actorId);
    }

    private function clearFlag(int $vendorId,string $column,int $actorId,?int $exceptId=null):void{$sql="UPDATE supplier_contacts SET {$column}=0,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE vendor_id=? AND deleted_at IS NULL".($exceptId!==null?' AND id<>?':'');$params=[$actorId,$vendorId];if($exceptId!==null)$params[]=$exceptId;$this->pdo->prepare($sql)->execute($params);}
    private function syncLegacy(int $vendorId,?string $name,?string $phone,?string $email,int $actorId):void{$this->pdo->prepare('UPDATE vendors SET contact_name=?,phone=?,email=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$name,$phone,$email,$actorId,$vendorId]);}
    private function transaction(callable $callback):mixed{$owns=!$this->pdo->inTransaction();if($owns)$this->pdo->beginTransaction();try{$result=$callback();if($owns)$this->pdo->commit();return $result;}catch(\Throwable $e){if($owns&&$this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}}
}
