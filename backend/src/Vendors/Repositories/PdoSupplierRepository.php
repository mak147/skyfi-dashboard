<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\SupplierRepositoryContract;
use SkyFi\Vendors\DomainModels\Supplier;
use SkyFi\Vendors\DTOs\SupplierData;
use SkyFi\Vendors\DTOs\SupplierListFilters;

final class PdoSupplierRepository implements SupplierRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(SupplierListFilters $filters): array
    {
        $where = $filters->includeArchived ? ['1=1'] : ['v.deleted_at IS NULL'];
        $params = [];
        if ($filters->search !== null) {
            $where[] = '(v.code LIKE :search_code OR v.name LIKE :search_name OR v.contact_name LIKE :search_contact OR v.email LIKE :search_email OR v.phone LIKE :search_phone OR v.tax_id LIKE :search_tax)';
            $like = '%' . $filters->search . '%';
            $params += ['search_code' => $like, 'search_name' => $like, 'search_contact' => $like, 'search_email' => $like, 'search_phone' => $like, 'search_tax' => $like];
        }
        if ($filters->status !== null) { $where[] = 'v.status = :status'; $params['status'] = $filters->status; }
        if ($filters->country !== null) { $where[] = 'v.country = :country'; $params['country'] = $filters->country; }
        if ($filters->categoryId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM supplier_category_assignments sca WHERE sca.vendor_id = v.id AND sca.supplier_category_id = :category_id AND sca.deleted_at IS NULL)';
            $params['category_id'] = $filters->categoryId;
        }
        if ($filters->minimumRating !== null) {
            $where[] = 'COALESCE((SELECT sr.overall_rating FROM supplier_ratings sr WHERE sr.vendor_id = v.id AND sr.deleted_at IS NULL ORDER BY sr.review_period_end DESC, sr.id DESC LIMIT 1), 0) >= :minimum_rating';
            $params['minimum_rating'] = $filters->minimumRating;
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM vendors v WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sortDescending = str_starts_with($filters->sort, '-');
        $sortKey = ltrim($filters->sort, '-');
        $sortMap = ['supplier_code' => 'v.code', 'company_name' => 'v.name', 'status' => 'v.status', 'country' => 'v.country', 'overall_rating' => 'overall_rating', 'procurement_value' => 'procurement_value', 'created_at' => 'v.created_at'];
        $sort = $sortMap[$sortKey] ?? 'v.created_at';
        $direction = $sortDescending ? 'DESC' : 'ASC';
        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT v.*,
                    (SELECT sr.overall_rating FROM supplier_ratings sr WHERE sr.vendor_id=v.id AND sr.deleted_at IS NULL ORDER BY sr.review_period_end DESC, sr.id DESC LIMIT 1) AS overall_rating,
                    (SELECT COALESCE(SUM(po.total_amount),0) FROM purchase_orders po WHERE po.vendor_id=v.id AND po.deleted_at IS NULL AND po.status NOT IN ('draft','rejected','cancelled') AND po.currency=v.currency) AS procurement_value,
                    (SELECT GROUP_CONCAT(sc.name ORDER BY sc.name SEPARATOR ', ') FROM supplier_category_assignments sca JOIN supplier_categories sc ON sc.id=sca.supplier_category_id WHERE sca.vendor_id=v.id AND sca.deleted_at IS NULL AND sc.deleted_at IS NULL) AS category_names,
                    (SELECT COUNT(*) FROM supplier_contacts c WHERE c.vendor_id=v.id AND c.deleted_at IS NULL) AS contact_count,
                    (SELECT COUNT(*) FROM supplier_contracts c WHERE c.vendor_id=v.id AND c.deleted_at IS NULL AND c.status='active') AS active_contract_count
                FROM vendors v WHERE {$whereSql} ORDER BY {$sort} {$direction}, v.id DESC LIMIT {$filters->perPage} OFFSET {$offset}";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $items = array_map(fn(array $row): Supplier => Supplier::fromRow($this->map($row)), $statement->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => max(1, (int) ceil($total / $filters->perPage))];
    }

    public function find(int $id): ?Supplier
    {
        $statement = $this->pdo->prepare("SELECT v.*,
                (SELECT sr.overall_rating FROM supplier_ratings sr WHERE sr.vendor_id=v.id AND sr.deleted_at IS NULL ORDER BY sr.review_period_end DESC, sr.id DESC LIMIT 1) AS overall_rating,
                (SELECT COALESCE(SUM(po.total_amount),0) FROM purchase_orders po WHERE po.vendor_id=v.id AND po.deleted_at IS NULL AND po.status NOT IN ('draft','rejected','cancelled') AND po.currency=v.currency) AS procurement_value,
                (SELECT COUNT(*) FROM purchase_orders po WHERE po.vendor_id=v.id AND po.deleted_at IS NULL) AS purchase_order_count,
                (SELECT COUNT(*) FROM supplier_contacts c WHERE c.vendor_id=v.id AND c.deleted_at IS NULL) AS contact_count,
                (SELECT COUNT(*) FROM supplier_contracts c WHERE c.vendor_id=v.id AND c.deleted_at IS NULL) AS contract_count,
                (SELECT COUNT(*) FROM supplier_quotations q WHERE q.vendor_id=v.id AND q.deleted_at IS NULL) AS quotation_count
            FROM vendors v WHERE v.id = ?");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $mapped = $this->map($row);
        $category = $this->pdo->prepare('SELECT sc.id, sc.code, sc.name FROM supplier_category_assignments sca JOIN supplier_categories sc ON sc.id=sca.supplier_category_id WHERE sca.vendor_id=? AND sca.deleted_at IS NULL AND sc.deleted_at IS NULL ORDER BY sc.name');
        $category->execute([$id]);
        $mapped['categories'] = $category->fetchAll(PDO::FETCH_ASSOC);
        return Supplier::fromRow($mapped);
    }

    public function create(SupplierData $data, int $actorId): Supplier
    {
        $statement = $this->pdo->prepare('INSERT INTO vendors (code,name,status,contact_name,email,phone,website,tax_id,registration_number,address,city,country,payment_terms,currency,notes,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $statement->execute([$data->supplierCode,$data->companyName,$data->status,$data->contactPerson,$data->email,$data->phone,$data->website,$data->taxNumber,$data->registrationNumber,$data->address,$data->city,$data->country,$data->paymentTerms,$data->currency,$data->notes,$actorId,$actorId]);
        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Supplier could not be loaded after creation.');
    }

    public function update(int $id, SupplierData $data, int $actorId): Supplier
    {
        $statement = $this->pdo->prepare('UPDATE vendors SET code=?,name=?,status=?,contact_name=COALESCE(?,contact_name),email=COALESCE(?,email),phone=COALESCE(?,phone),website=?,tax_id=?,registration_number=?,address=?,city=?,country=?,payment_terms=?,currency=?,notes=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $statement->execute([$data->supplierCode,$data->companyName,$data->status,$data->contactPerson,$data->email,$data->phone,$data->website,$data->taxNumber,$data->registrationNumber,$data->address,$data->city,$data->country,$data->paymentTerms,$data->currency,$data->notes,$actorId,$id]);
        return $this->find($id) ?? throw new \RuntimeException('Supplier could not be loaded after update.');
    }

    public function archive(int $id, int $actorId): Supplier
    {
        $this->pdo->prepare("UPDATE vendors SET status='archived', deleted_at=CURRENT_TIMESTAMP, updated_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$actorId,$id]);
        return $this->find($id) ?? throw new \RuntimeException('Supplier could not be loaded after archive.');
    }

    public function activate(int $id, int $actorId): Supplier
    {
        $this->pdo->prepare("UPDATE vendors SET status='active', deleted_at=NULL, updated_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$actorId,$id]);
        return $this->find($id) ?? throw new \RuntimeException('Supplier could not be loaded after activation.');
    }

    public function updateStatus(int $id, string $status, int $actorId): Supplier
    {
        $this->pdo->prepare("UPDATE vendors SET status=?, deleted_at=CASE WHEN ?='archived' THEN CURRENT_TIMESTAMP ELSE NULL END, updated_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$status,$status,$actorId,$id]);
        return $this->find($id) ?? throw new \RuntimeException('Supplier could not be loaded after status update.');
    }

    public function existsByCode(string $code, ?int $exceptId = null): bool { return $this->exists('code',$code,$exceptId); }
    public function existsByName(string $name, ?int $exceptId = null): bool { return $this->exists('name',$name,$exceptId); }

    public function syncCategories(int $vendorId, array $categoryIds, int $actorId): void
    {
        $this->pdo->prepare('UPDATE supplier_category_assignments SET deleted_at=CURRENT_TIMESTAMP, updated_by=? WHERE vendor_id=? AND deleted_at IS NULL')->execute([$actorId,$vendorId]);
        $statement=$this->pdo->prepare('INSERT INTO supplier_category_assignments (vendor_id,supplier_category_id,created_by,updated_by,deleted_at) VALUES (?,?,?,?,NULL) ON DUPLICATE KEY UPDATE deleted_at=NULL, updated_by=VALUES(updated_by), updated_at=CURRENT_TIMESTAMP');
        foreach($categoryIds as $categoryId)$statement->execute([$vendorId,$categoryId,$actorId,$actorId]);
    }

    public function categories(bool $activeOnly = false): array
    {
        $sql='SELECT sc.*, (SELECT COUNT(*) FROM supplier_category_assignments sca WHERE sca.supplier_category_id=sc.id AND sca.deleted_at IS NULL) AS supplier_count FROM supplier_categories sc WHERE sc.deleted_at IS NULL'.($activeOnly?" AND sc.status='active'":'').' ORDER BY sc.name';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCategory(array $data, int $actorId): array
    {
        $this->pdo->prepare('INSERT INTO supplier_categories (code,name,description,status,created_by,updated_by) VALUES (?,?,?,?,?,?)')->execute([strtoupper(trim((string)$data['code'])),trim((string)$data['name']),isset($data['description'])?trim((string)$data['description']):null,$data['status']??'active',$actorId,$actorId]);
        return $this->findCategory((int)$this->pdo->lastInsertId());
    }

    public function updateCategory(int $id, array $data, int $actorId): array
    {
        $this->pdo->prepare('UPDATE supplier_categories SET code=?,name=?,description=?,status=?,updated_by=? WHERE id=? AND deleted_at IS NULL')->execute([strtoupper(trim((string)$data['code'])),trim((string)$data['name']),isset($data['description'])?trim((string)$data['description']):null,$data['status']??'active',$actorId,$id]);
        return $this->findCategory($id);
    }

    public function deleteCategory(int $id, int $actorId): void
    {
        $this->pdo->prepare("UPDATE supplier_categories SET status='inactive',deleted_at=CURRENT_TIMESTAMP,updated_by=? WHERE id=?")->execute([$actorId,$id]);
    }

    public function purchaseOrders(int $vendorId, int $limit = 100): array
    {
        $limit=min(100,max(1,$limit));$statement=$this->pdo->prepare("SELECT po.id,po.po_number,po.status,po.currency,po.total_amount,po.order_date,po.expected_delivery_date,po.delivery_date,w.name AS warehouse_name FROM purchase_orders po LEFT JOIN warehouses w ON w.id=po.warehouse_id WHERE po.vendor_id=? AND po.deleted_at IS NULL ORDER BY po.order_date DESC,po.id DESC LIMIT {$limit}");$statement->execute([$vendorId]);return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function products(int $vendorId): array
    {
        $statement=$this->pdo->prepare('SELECT p.id,p.sku,p.name,p.status,p.tracking_mode,pv.vendor_sku,pv.is_default,pv.last_purchase_cost,pv.lead_time_days,u.symbol AS unit_symbol FROM inventory_product_vendors pv JOIN inventory_products p ON p.id=pv.product_id LEFT JOIN inventory_units u ON u.id=p.unit_id WHERE pv.vendor_id=? AND p.deleted_at IS NULL ORDER BY pv.is_default DESC,p.name');$statement->execute([$vendorId]);return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function financialReferences(int $vendorId): array
    {
        $invoice=$this->pdo->prepare('SELECT si.id,si.invoice_number,si.status,si.currency,si.total_amount,si.invoice_date,si.due_date,po.po_number FROM supplier_invoices si LEFT JOIN purchase_orders po ON po.id=si.purchase_order_id WHERE si.vendor_id=? AND si.deleted_at IS NULL ORDER BY si.invoice_date DESC LIMIT 100');$invoice->execute([$vendorId]);$invoices=$invoice->fetchAll(PDO::FETCH_ASSOC);
        $summary=$this->pdo->prepare("SELECT currency,COUNT(*) AS invoice_count,COALESCE(SUM(total_amount),0) AS invoiced_total,COALESCE(SUM(CASE WHEN status<>'paid' THEN total_amount ELSE 0 END),0) AS open_total FROM supplier_invoices WHERE vendor_id=? AND deleted_at IS NULL GROUP BY currency");$summary->execute([$vendorId]);
        $postings=$this->pdo->prepare("SELECT pfp.id,pfp.source_type,pfp.source_id,pfp.status,pfp.idempotency_key,pfp.last_error,pfp.created_at,je.transaction_id,je.description AS journal_description FROM purchasing_finance_postings pfp LEFT JOIN journal_entries je ON je.id=pfp.journal_entry_id WHERE (pfp.source_type='purchase_order' AND pfp.source_id IN (SELECT po.id FROM purchase_orders po WHERE po.vendor_id=?)) OR (pfp.source_type='goods_receipt' AND pfp.source_id IN (SELECT gr.id FROM goods_receipts gr JOIN purchase_orders po ON po.id=gr.purchase_order_id WHERE po.vendor_id=?)) ORDER BY pfp.created_at DESC LIMIT 100");$postings->execute([$vendorId,$vendorId]);
        return ['invoices'=>$invoices,'summary_by_currency'=>$summary->fetchAll(PDO::FETCH_ASSOC),'finance_postings'=>$postings->fetchAll(PDO::FETCH_ASSOC)];
    }

    private function exists(string $column,string $value,?int $exceptId):bool{$sql="SELECT COUNT(*) FROM vendors WHERE {$column}=?".($exceptId!==null?' AND id<>?':'');$params=[$value];if($exceptId!==null)$params[]=$exceptId;$statement=$this->pdo->prepare($sql);$statement->execute($params);return (int)$statement->fetchColumn()>0;}
    /** @return array<string,mixed> */ private function findCategory(int $id):array{$statement=$this->pdo->prepare('SELECT * FROM supplier_categories WHERE id=? AND deleted_at IS NULL');$statement->execute([$id]);return $statement->fetch(PDO::FETCH_ASSOC)?:[];}
    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function map(array $row):array
    {
        return ['id'=>(int)$row['id'],'supplier_code'=>$row['code'],'company_name'=>$row['name'],'tax_number'=>$row['tax_id'],'registration_number'=>$row['registration_number']??null,'address'=>$row['address']??null,'city'=>$row['city']??null,'country'=>$row['country']??null,'contact_person'=>$row['contact_name'],'phone'=>$row['phone'],'email'=>$row['email'],'website'=>$row['website'],'payment_terms'=>$row['payment_terms'],'currency'=>$row['currency']??'PKR','notes'=>$row['notes'],'status'=>$row['status'],'overall_rating'=>isset($row['overall_rating'])&&$row['overall_rating']!==null?(float)$row['overall_rating']:null,'procurement_value'=>(float)($row['procurement_value']??0),'category_names'=>$row['category_names']??null,'contact_count'=>(int)($row['contact_count']??0),'active_contract_count'=>(int)($row['active_contract_count']??0),'purchase_order_count'=>(int)($row['purchase_order_count']??0),'contract_count'=>(int)($row['contract_count']??0),'quotation_count'=>(int)($row['quotation_count']??0),'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],'deleted_at'=>$row['deleted_at']];
    }
}
