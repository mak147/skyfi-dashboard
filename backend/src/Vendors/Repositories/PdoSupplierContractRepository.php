<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\SupplierContractRepositoryContract;
use SkyFi\Vendors\DomainModels\SupplierContract;
use SkyFi\Vendors\DTOs\ContractData;
use SkyFi\Vendors\DTOs\ContractListFilters;

final class PdoSupplierContractRepository implements SupplierContractRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}
    public function list(ContractListFilters $filters):array
    {
        $where=['c.deleted_at IS NULL','v.deleted_at IS NULL'];$params=[];
        if($filters->search!==null){$where[]='(c.contract_number LIKE :search_number OR c.notes LIKE :search_notes OR v.name LIKE :search_vendor)';$like='%'.$filters->search.'%';$params+=['search_number'=>$like,'search_notes'=>$like,'search_vendor'=>$like];}
        if($filters->vendorId!==null){$where[]='c.vendor_id=:vendor';$params['vendor']=$filters->vendorId;}
        if($filters->status!==null){$where[]='c.status=:status';$params['status']=$filters->status;}
        if($filters->expiringBefore!==null){$where[]="c.status='active' AND COALESCE(c.renewal_date,c.end_date)<=:expiring";$params['expiring']=$filters->expiringBefore;}
        $whereSql=implode(' AND ',$where);$count=$this->pdo->prepare("SELECT COUNT(*) FROM supplier_contracts c JOIN vendors v ON v.id=c.vendor_id WHERE {$whereSql}");$count->execute($params);$total=(int)$count->fetchColumn();
        $desc=str_starts_with($filters->sort,'-');$key=ltrim($filters->sort,'-');$map=['contract_number'=>'c.contract_number','start_date'=>'c.start_date','end_date'=>'c.end_date','renewal_date'=>'c.renewal_date','contract_value'=>'c.contract_value','status'=>'c.status','created_at'=>'c.created_at'];$sort=$map[$key]??'c.end_date';$offset=($filters->page-1)*$filters->perPage;
        $statement=$this->pdo->prepare("SELECT c.*,v.name AS supplier_name,v.code AS supplier_code,DATEDIFF(c.end_date,CURDATE()) AS days_remaining,(c.status='active' AND COALESCE(c.renewal_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)) AS is_expiring FROM supplier_contracts c JOIN vendors v ON v.id=c.vendor_id WHERE {$whereSql} ORDER BY {$sort} ".($desc?'DESC':'ASC').",c.id DESC LIMIT {$filters->perPage} OFFSET {$offset}");$statement->execute($params);
        return ['items'=>array_map(static fn(array $row):SupplierContract=>SupplierContract::fromRow($row),$statement->fetchAll(PDO::FETCH_ASSOC)),'total'=>$total,'page'=>$filters->page,'perPage'=>$filters->perPage,'lastPage'=>max(1,(int)ceil($total/$filters->perPage))];
    }
    public function find(int $id):?SupplierContract{$statement=$this->pdo->prepare("SELECT c.*,v.name AS supplier_name,v.code AS supplier_code,DATEDIFF(c.end_date,CURDATE()) AS days_remaining,(c.status='active' AND COALESCE(c.renewal_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)) AS is_expiring FROM supplier_contracts c JOIN vendors v ON v.id=c.vendor_id WHERE c.id=? AND c.deleted_at IS NULL");$statement->execute([$id]);$row=$statement->fetch(PDO::FETCH_ASSOC);return $row?SupplierContract::fromRow($row):null;}
    public function create(int $vendorId,ContractData $data,int $actorId):SupplierContract{$this->pdo->prepare('INSERT INTO supplier_contracts (vendor_id,contract_number,start_date,end_date,renewal_date,contract_value,currency,status,attachment_name,attachment_reference,notes,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$vendorId,$data->contractNumber,$data->startDate,$data->endDate,$data->renewalDate,$data->contractValue,$data->currency,$data->status,$data->attachmentName,$data->attachmentReference,$data->notes,$actorId,$actorId]);return $this->find((int)$this->pdo->lastInsertId())??throw new \RuntimeException('Contract could not be loaded.');}
    public function update(int $id,ContractData $data,int $actorId):SupplierContract{$this->pdo->prepare('UPDATE supplier_contracts SET contract_number=?,start_date=?,end_date=?,renewal_date=?,contract_value=?,currency=?,status=?,attachment_name=?,attachment_reference=?,notes=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND deleted_at IS NULL')->execute([$data->contractNumber,$data->startDate,$data->endDate,$data->renewalDate,$data->contractValue,$data->currency,$data->status,$data->attachmentName,$data->attachmentReference,$data->notes,$actorId,$id]);return $this->find($id)??throw new \RuntimeException('Contract could not be loaded.');}
    public function delete(int $id,int $actorId):void{$this->pdo->prepare("UPDATE supplier_contracts SET status='terminated',deleted_at=CURRENT_TIMESTAMP,updated_by=? WHERE id=?")->execute([$actorId,$id]);}
    public function numberExists(string $number,?int $exceptId=null):bool{$sql='SELECT COUNT(*) FROM supplier_contracts WHERE contract_number=?'.($exceptId!==null?' AND id<>?':'');$params=[$number];if($exceptId!==null)$params[]=$exceptId;$statement=$this->pdo->prepare($sql);$statement->execute($params);return (int)$statement->fetchColumn()>0;}
}
