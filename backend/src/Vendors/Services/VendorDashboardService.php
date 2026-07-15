<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Services;
use PDO;
final class VendorDashboardService
{
 public function __construct(private readonly PDO $pdo){}
 /** @return array<string,mixed> */public function dashboard():array
 {
  $active=(int)$this->pdo->query("SELECT COUNT(*) FROM vendors WHERE status='active' AND deleted_at IS NULL")->fetchColumn();
  $expiring=(int)$this->pdo->query("SELECT COUNT(*) FROM supplier_contracts WHERE status='active' AND deleted_at IS NULL AND COALESCE(renewal_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)")->fetchColumn();
  $average=(float)$this->pdo->query("SELECT COALESCE(AVG(sr.overall_rating),0) FROM supplier_ratings sr WHERE sr.deleted_at IS NULL AND sr.id=(SELECT sr2.id FROM supplier_ratings sr2 WHERE sr2.vendor_id=sr.vendor_id AND sr2.deleted_at IS NULL ORDER BY sr2.review_period_end DESC,sr2.id DESC LIMIT 1)")->fetchColumn();
  $top=$this->pdo->query("SELECT v.id,v.code AS supplier_code,v.name AS company_name,sr.overall_rating,sr.delivery_performance_pct,sr.product_quality_score FROM vendors v JOIN supplier_ratings sr ON sr.id=(SELECT sr2.id FROM supplier_ratings sr2 WHERE sr2.vendor_id=v.id AND sr2.deleted_at IS NULL ORDER BY sr2.review_period_end DESC,sr2.id DESC LIMIT 1) WHERE v.deleted_at IS NULL ORDER BY sr.overall_rating DESC,v.name LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
  $spend=$this->pdo->query("SELECT v.id,v.name AS company_name,po.currency,COALESCE(SUM(po.total_amount),0) AS amount FROM purchase_orders po JOIN vendors v ON v.id=po.vendor_id WHERE po.deleted_at IS NULL AND v.deleted_at IS NULL AND po.status NOT IN ('draft','rejected','cancelled') GROUP BY v.id,v.name,po.currency ORDER BY amount DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
  $statuses=$this->pdo->query("SELECT status,COUNT(*) AS total FROM vendors WHERE deleted_at IS NULL GROUP BY status ORDER BY status")->fetchAll(PDO::FETCH_ASSOC);
  $contracts=$this->pdo->query("SELECT c.id,c.contract_number,c.end_date,c.renewal_date,v.id AS vendor_id,v.name AS supplier_name,DATEDIFF(COALESCE(c.renewal_date,c.end_date),CURDATE()) AS days_remaining FROM supplier_contracts c JOIN vendors v ON v.id=c.vendor_id WHERE c.status='active' AND c.deleted_at IS NULL AND v.deleted_at IS NULL AND COALESCE(c.renewal_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) ORDER BY COALESCE(c.renewal_date,c.end_date) LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
  return ['active_suppliers'=>$active,'expiring_contracts'=>$expiring,'average_supplier_rating'=>round($average,2),'top_suppliers'=>$top,'procurement_spend_by_supplier'=>$spend,'suppliers_by_status'=>$statuses,'expiring_contract_items'=>$contracts];
 }
}
