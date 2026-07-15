<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\SupplierQuotationRepositoryContract;
use SkyFi\Vendors\DomainModels\SupplierQuotation;
use SkyFi\Vendors\DTOs\QuotationData;
use SkyFi\Vendors\DTOs\QuotationListFilters;

final class PdoSupplierQuotationRepository implements SupplierQuotationRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}
    public function list(QuotationListFilters $filters):array
    {
        $where=['q.deleted_at IS NULL','v.deleted_at IS NULL'];$params=[];
        if($filters->search!==null){$where[]='(q.quotation_number LIKE :search_number OR q.rfq_reference LIKE :search_rfq OR v.name LIKE :search_vendor)';$like='%'.$filters->search.'%';$params+=['search_number'=>$like,'search_rfq'=>$like,'search_vendor'=>$like];}
        if($filters->vendorId!==null){$where[]='q.vendor_id=:vendor';$params['vendor']=$filters->vendorId;}
        if($filters->status!==null){$where[]='q.status=:status';$params['status']=$filters->status;}
        if($filters->rfqReference!==null){$where[]='q.rfq_reference=:rfq';$params['rfq']=$filters->rfqReference;}
        if($filters->currency!==null){$where[]='q.currency=:currency';$params['currency']=$filters->currency;}
        $whereSql=implode(' AND ',$where);$count=$this->pdo->prepare("SELECT COUNT(*) FROM supplier_quotations q JOIN vendors v ON v.id=q.vendor_id WHERE {$whereSql}");$count->execute($params);$total=(int)$count->fetchColumn();
        $desc=str_starts_with($filters->sort,'-');$key=ltrim($filters->sort,'-');$map=['quotation_number'=>'q.quotation_number','quotation_date'=>'q.quotation_date','valid_until'=>'q.valid_until','total_amount'=>'q.total_amount','status'=>'q.status','created_at'=>'q.created_at'];$sort=$map[$key]??'q.quotation_date';$offset=($filters->page-1)*$filters->perPage;
        $statement=$this->pdo->prepare("SELECT q.*,v.name AS supplier_name,v.code AS supplier_code,(SELECT COUNT(*) FROM supplier_quotation_items qi WHERE qi.supplier_quotation_id=q.id AND qi.deleted_at IS NULL) AS item_count FROM supplier_quotations q JOIN vendors v ON v.id=q.vendor_id WHERE {$whereSql} ORDER BY {$sort} ".($desc?'DESC':'ASC').",q.id DESC LIMIT {$filters->perPage} OFFSET {$offset}");$statement->execute($params);
        return ['items'=>array_map(static fn(array $row):SupplierQuotation=>SupplierQuotation::fromRow($row),$statement->fetchAll(PDO::FETCH_ASSOC)),'total'=>$total,'page'=>$filters->page,'perPage'=>$filters->perPage,'lastPage'=>max(1,(int)ceil($total/$filters->perPage))];
    }
    public function find(int $id):?SupplierQuotation
    {
        $statement=$this->pdo->prepare('SELECT q.*,v.name AS supplier_name,v.code AS supplier_code FROM supplier_quotations q JOIN vendors v ON v.id=q.vendor_id WHERE q.id=? AND q.deleted_at IS NULL');$statement->execute([$id]);$row=$statement->fetch(PDO::FETCH_ASSOC);if(!$row)return null;
        $items=$this->pdo->prepare('SELECT qi.*,p.sku,p.name AS product_name FROM supplier_quotation_items qi LEFT JOIN inventory_products p ON p.id=qi.product_id WHERE qi.supplier_quotation_id=? AND qi.deleted_at IS NULL ORDER BY qi.id');$items->execute([$id]);$row['items']=$items->fetchAll(PDO::FETCH_ASSOC);return SupplierQuotation::fromRow($row);
    }
    public function create(int $vendorId,QuotationData $data,int $actorId):SupplierQuotation
    {
        return $this->transaction(function()use($vendorId,$data,$actorId){[$subtotal,$total]=$this->totals($data);$this->pdo->prepare('INSERT INTO supplier_quotations (vendor_id,quotation_number,rfq_reference,quotation_date,valid_until,currency,subtotal,tax_amount,total_amount,status,notes,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$vendorId,$data->quotationNumber,$data->rfqReference,$data->quotationDate,$data->validUntil,$data->currency,$subtotal,$data->taxAmount,$total,$data->status,$data->notes,$actorId,$actorId]);$id=(int)$this->pdo->lastInsertId();$this->insertItems($id,$data,$actorId);return $this->find($id)??throw new \RuntimeException('Quotation could not be loaded.');});
    }
    public function update(int $id,QuotationData $data,int $actorId):SupplierQuotation
    {
        return $this->transaction(function()use($id,$data,$actorId){[$subtotal,$total]=$this->totals($data);$this->pdo->prepare('UPDATE supplier_quotations SET quotation_number=?,rfq_reference=?,quotation_date=?,valid_until=?,currency=?,subtotal=?,tax_amount=?,total_amount=?,status=?,notes=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND deleted_at IS NULL')->execute([$data->quotationNumber,$data->rfqReference,$data->quotationDate,$data->validUntil,$data->currency,$subtotal,$data->taxAmount,$total,$data->status,$data->notes,$actorId,$id]);$this->pdo->prepare('UPDATE supplier_quotation_items SET deleted_at=CURRENT_TIMESTAMP,updated_by=? WHERE supplier_quotation_id=? AND deleted_at IS NULL')->execute([$actorId,$id]);$this->insertItems($id,$data,$actorId);return $this->find($id)??throw new \RuntimeException('Quotation could not be loaded.');});
    }
    public function delete(int $id,int $actorId):void{$this->pdo->prepare('UPDATE supplier_quotations SET deleted_at=CURRENT_TIMESTAMP,updated_by=? WHERE id=?')->execute([$actorId,$id]);$this->pdo->prepare('UPDATE supplier_quotation_items SET deleted_at=CURRENT_TIMESTAMP,updated_by=? WHERE supplier_quotation_id=?')->execute([$actorId,$id]);}
    public function numberExists(int $vendorId,string $number,?int $exceptId=null):bool{$sql='SELECT COUNT(*) FROM supplier_quotations WHERE vendor_id=? AND quotation_number=?'.($exceptId!==null?' AND id<>?':'');$params=[$vendorId,$number];if($exceptId!==null)$params[]=$exceptId;$statement=$this->pdo->prepare($sql);$statement->execute($params);return (int)$statement->fetchColumn()>0;}
    public function compare(string $rfqReference,?int $productId=null):array
    {
        $sql="SELECT q.id AS quotation_id,q.quotation_number,q.rfq_reference,q.quotation_date,q.valid_until,q.currency,q.status,v.id AS vendor_id,v.name AS supplier_name,qi.id AS item_id,qi.product_id,COALESCE(p.name,qi.description) AS item_name,p.sku,qi.description,qi.quantity,qi.unit_price,qi.line_total,qi.lead_time_days,MIN(qi.unit_price) OVER (PARTITION BY q.currency,COALESCE(CAST(qi.product_id AS CHAR),LOWER(qi.description))) AS best_unit_price FROM supplier_quotations q JOIN vendors v ON v.id=q.vendor_id JOIN supplier_quotation_items qi ON qi.supplier_quotation_id=q.id LEFT JOIN inventory_products p ON p.id=qi.product_id WHERE q.rfq_reference=? AND q.deleted_at IS NULL AND qi.deleted_at IS NULL AND q.status NOT IN ('rejected','expired')".($productId!==null?' AND qi.product_id=?':'').' ORDER BY q.currency,item_name,qi.unit_price';$params=[$rfqReference];if($productId!==null)$params[]=$productId;$statement=$this->pdo->prepare($sql);$statement->execute($params);$rows=$statement->fetchAll(PDO::FETCH_ASSOC);foreach($rows as &$row)$row['is_best_price']=(float)$row['unit_price']===(float)$row['best_unit_price'];return $rows;
    }
    public function history(int $quotationId):array{$statement=$this->pdo->prepare("SELECT al.id,al.action,al.old_values,al.new_values,al.created_at,u.name AS actor_name FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id WHERE al.entity_type='supplier_quotation' AND al.entity_id=? ORDER BY al.created_at DESC,al.id DESC");$statement->execute([$quotationId]);$rows=$statement->fetchAll(PDO::FETCH_ASSOC);foreach($rows as &$row){$row['old_values']=$row['old_values']?json_decode((string)$row['old_values'],true):null;$row['new_values']=$row['new_values']?json_decode((string)$row['new_values'],true):null;}return $rows;}
    /** @return array{0:float,1:float} */private function totals(QuotationData $data):array{$subtotal=0.0;foreach($data->items as $item)$subtotal+=round((float)$item['quantity']*(float)$item['unit_price'],4);return [round($subtotal,4),round($subtotal+$data->taxAmount,4)];}
    private function insertItems(int $quotationId,QuotationData $data,int $actorId):void{$statement=$this->pdo->prepare('INSERT INTO supplier_quotation_items (supplier_quotation_id,product_id,description,quantity,unit_price,line_total,lead_time_days,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?)');foreach($data->items as $item){$quantity=(float)$item['quantity'];$price=(float)$item['unit_price'];$statement->execute([$quotationId,isset($item['product_id'])&&(int)$item['product_id']>0?(int)$item['product_id']:null,trim((string)$item['description']),$quantity,$price,round($quantity*$price,4),isset($item['lead_time_days'])&&$item['lead_time_days']!==''?(int)$item['lead_time_days']:null,$actorId,$actorId]);}}
    private function transaction(callable $callback):mixed{$owns=!$this->pdo->inTransaction();if($owns)$this->pdo->beginTransaction();try{$result=$callback();if($owns)$this->pdo->commit();return $result;}catch(\Throwable $e){if($owns&&$this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}}
}
