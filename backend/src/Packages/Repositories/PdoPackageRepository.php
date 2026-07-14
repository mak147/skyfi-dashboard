<?php

declare(strict_types=1);
namespace SkyFi\Packages\Repositories;
use PDO;
use SkyFi\Packages\Contracts\PackageRepositoryContract;
use SkyFi\Packages\Data\PackageListFilters;
use SkyFi\Packages\Models\InternetPackage;

final class PdoPackageRepository implements PackageRepositoryContract
{
 public function __construct(private readonly PDO $pdo) {}
 public function find(int $id): ?InternetPackage {
  $s=$this->pdo->prepare("SELECT p.*,c.code category,c.name category_name FROM packages p JOIN package_categories c ON c.id=p.category_id WHERE p.id=:id AND p.deleted_at IS NULL");$s->execute(['id'=>$id]);$row=$s->fetch(); if(!$row)return null;
  $row['pricing']=$this->normalize($this->pricing($id));
  $row['bandwidth']=$this->normalize($this->one('package_bandwidth_profiles',$id));
  $row['network']=$this->normalize($this->one('package_network_profiles',$id));
  $row['customer_rules']=$this->normalize($this->one('package_customer_rules',$id));
  $row['billing']=$this->normalize($this->one('package_billing_settings',$id));
  $row['technical']=$this->normalize($this->one('package_technical_profiles',$id));
  return new InternetPackage($this->normalize($row));
 }
 public function codeExists(string $code,?int $excludeId=null): bool { $sql='SELECT 1 FROM packages WHERE code=:code'.($excludeId?' AND id<>:id':'').' LIMIT 1';$s=$this->pdo->prepare($sql);$p=['code'=>$code];if($excludeId)$p['id']=$excludeId;$s->execute($p);return (bool)$s->fetchColumn(); }
 public function list(PackageListFilters $f): array {
  $where=['p.deleted_at IS NULL'];$p=[];
  if($f->search){$where[]='(p.name LIKE :search OR p.code LIKE :search OR p.description LIKE :search)';$p['search']='%'.$f->search.'%';}
  if($f->status){$where[]='p.status=:status';$p['status']=$f->status;}
  if($f->category){$where[]='c.code=:category';$p['category']=$f->category;}
  if($f->billingCycle){$where[]='bs.default_billing_cycle=:cycle';$p['cycle']=$f->billingCycle;}
  if($f->unlimited!==null){$where[]='bw.is_unlimited=:unlimited';$p['unlimited']=$f->unlimited?1:0;}
  $from=' FROM packages p JOIN package_categories c ON c.id=p.category_id LEFT JOIN package_prices mp ON mp.package_id=p.id AND mp.billing_period="monthly" LEFT JOIN package_bandwidth_profiles bw ON bw.package_id=p.id LEFT JOIN package_billing_settings bs ON bs.package_id=p.id';$w=' WHERE '.implode(' AND ',$where);
  $count=$this->pdo->prepare('SELECT COUNT(*)'.$from.$w);$count->execute($p);$total=(int)$count->fetchColumn();
  $sort=['name'=>'p.name','code'=>'p.code','status'=>'p.status','category'=>'c.name','monthly_price'=>'mp.amount','download_speed'=>'bw.download_kbps','created_at'=>'p.created_at','updated_at'=>'p.updated_at'][$f->sort];
  $sql='SELECT p.id,p.name,p.code,p.description,p.status,p.created_at,p.updated_at,c.code category,c.name category_name,mp.amount monthly_price,bw.download_kbps,bw.upload_kbps,bw.is_unlimited'.$from.$w." ORDER BY $sort ".($f->descending?'DESC':'ASC').', p.id DESC LIMIT :limit OFFSET :offset';
  $s=$this->pdo->prepare($sql);foreach($p as $k=>$v)$s->bindValue(':'.$k,$v);$s->bindValue(':limit',$f->perPage,PDO::PARAM_INT);$s->bindValue(':offset',($f->page-1)*$f->perPage,PDO::PARAM_INT);$s->execute();
  $items=array_map(fn($r)=>new InternetPackage($this->normalize($r)),$s->fetchAll());$last=max(1,(int)ceil($total/$f->perPage));return ['items'=>$items,'total'=>$total,'page'=>$f->page,'perPage'=>$f->perPage,'lastPage'=>$last];
 }
 public function create(array $d,int $userId): InternetPackage { $this->pdo->beginTransaction();try{$cat=$this->categoryId($d['category']);$s=$this->pdo->prepare('INSERT INTO packages(category_id,name,code,description,status,created_by) VALUES(?,?,?,?,?,?)');$s->execute([$cat,$d['name'],$d['code'],$d['description'],$d['status'],$userId]);$id=(int)$this->pdo->lastInsertId();$this->writeChildren($id,$d);$this->pdo->commit();return $this->find($id)??throw new \RuntimeException('Created package could not be read.');}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;} }
 public function update(int $id,array $d,int $userId): InternetPackage { $this->pdo->beginTransaction();try{$s=$this->pdo->prepare('UPDATE packages SET category_id=?,name=?,code=?,description=?,status=?,updated_by=? WHERE id=? AND deleted_at IS NULL');$s->execute([$this->categoryId($d['category']),$d['name'],$d['code'],$d['description'],$d['status'],$userId,$id]);$this->writeChildren($id,$d);$this->pdo->commit();return $this->find($id)??throw new \RuntimeException('Updated package could not be read.');}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;} }
 public function changeStatus(int $id,string $status,int $userId): InternetPackage { $s=$this->pdo->prepare('UPDATE packages SET status=?,updated_by=? WHERE id=? AND deleted_at IS NULL');$s->execute([$status,$userId,$id]);return $this->find($id)??throw new \RuntimeException('Package could not be read.'); }
 public function softDelete(int $id): void { $s=$this->pdo->prepare('UPDATE packages SET deleted_at=CURRENT_TIMESTAMP WHERE id=? AND deleted_at IS NULL');$s->execute([$id]); }
 public function isInUse(int $id): bool { $s=$this->pdo->prepare('SELECT 1 FROM customers WHERE assigned_package_id=? AND deleted_at IS NULL LIMIT 1');$s->execute([$id]);return (bool)$s->fetchColumn(); }
 public function statistics(): array { $base=' FROM packages WHERE deleted_at IS NULL';return ['total'=>(int)$this->pdo->query('SELECT COUNT(*)'.$base)->fetchColumn(),'active'=>(int)$this->pdo->query("SELECT COUNT(*)$base AND status='active'")->fetchColumn(),'draft'=>(int)$this->pdo->query("SELECT COUNT(*)$base AND status='draft'")->fetchColumn(),'inactive'=>(int)$this->pdo->query("SELECT COUNT(*)$base AND status IN ('inactive','archived')")->fetchColumn(),'by_category'=>$this->pdo->query('SELECT c.code,c.name,COUNT(p.id) count FROM package_categories c LEFT JOIN packages p ON p.category_id=c.id AND p.deleted_at IS NULL GROUP BY c.id,c.code,c.name ORDER BY c.id')->fetchAll()]; }
 public function activity(int $id): array { $s=$this->pdo->prepare("SELECT a.id,a.user_id,a.action,a.old_values,a.new_values,a.ip_address,a.created_at,u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE a.entity_type='package' AND a.entity_id=? ORDER BY a.created_at DESC LIMIT 100");$s->execute([$id]);return array_map(function($r){$r['old_values']=$r['old_values']?json_decode($r['old_values'],true):null;$r['new_values']=$r['new_values']?json_decode($r['new_values'],true):null;return $r;},$s->fetchAll()); }
 private function categoryId(string $code): int {$s=$this->pdo->prepare('SELECT id FROM package_categories WHERE code=?');$s->execute([$code]);return (int)$s->fetchColumn();}
 private function pricing(int $id): array { $s=$this->pdo->prepare('SELECT billing_period,amount FROM package_prices WHERE package_id=?');$s->execute([$id]);$out=[];foreach($s->fetchAll() as $r)$out[$r['billing_period']]=$r['amount'];return [...$out,...$this->one('package_pricing_settings',$id)]; }
 private function one(string $table,int $id): array {$s=$this->pdo->prepare("SELECT * FROM $table WHERE package_id=?");$s->execute([$id]);return $s->fetch()?:[];}
 private function writeChildren(int $id,array $d): void {
  foreach(['monthly','quarterly','semi_annual','annual'] as $period){$s=$this->pdo->prepare('INSERT INTO package_prices(package_id,billing_period,amount) VALUES(?,?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)');$s->execute([$id,$period,$d['pricing'][$period]]);}
  $this->upsert('package_pricing_settings',$id,['installation_charge'=>$d['pricing']['installation_charge'],'supports_tax'=>$d['pricing']['supports_tax'],'supports_discount'=>$d['pricing']['supports_discount']]);
  $this->upsert('package_bandwidth_profiles',$id,$d['bandwidth']);$this->upsert('package_network_profiles',$id,$d['network']);$this->upsert('package_customer_rules',$id,$d['customer_rules']);$this->upsert('package_billing_settings',$id,$d['billing']);$this->upsert('package_technical_profiles',$id,$d['technical']);
 }
 private function upsert(string $table,int $id,array $d): void {$cols=array_keys($d);$names=implode(',',$cols);$marks=implode(',',array_fill(0,count($cols),'?'));$updates=implode(',',array_map(fn($c)=>"$c=VALUES($c)",$cols));$s=$this->pdo->prepare("INSERT INTO $table(package_id,$names) VALUES(?,$marks) ON DUPLICATE KEY UPDATE $updates");$s->execute([$id,...array_values(array_map(fn($v)=>is_bool($v)?(int)$v:$v,$d))]);}
 private function normalize(array $r): array { foreach(['id','category_id','created_by','updated_by','download_kbps','upload_kbps','burst_download_kbps','burst_upload_kbps','cir_kbps','mir_kbps','data_limit_bytes','vlan_id','max_devices','grace_period_days'] as $k)if(array_key_exists($k,$r)&&$r[$k]!==null)$r[$k]=(int)$r[$k];foreach(['is_unlimited','supports_tax','supports_discount','allows_static_ip','allows_public_ip','allows_dynamic_ip','auto_renew','supports_late_fee'] as $k)if(array_key_exists($k,$r))$r[$k]=(bool)$r[$k];return $r;}
}
