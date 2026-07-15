<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Services;
use SkyFi\FieldService\Contracts\FieldServiceRepositoryContract;
use SkyFi\FieldService\DTOs\{FieldServiceListFilters,TechnicianData};
use SkyFi\FieldService\Validators\FieldServiceValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\{NotFoundException,ValidationException};
final class TechnicianService
{
 public function __construct(private readonly FieldServiceRepositoryContract$repo,private readonly FieldServiceValidator$validator,private readonly AuditLoggerContract$audit){}
 public function list(FieldServiceListFilters$f):array{return$this->repo->listTechnicians($f);} public function get(int$id):array{return$this->repo->findTechnician($id)??throw new NotFoundException('Technician not found.');}
 public function create(TechnicianData$d,int$a):array{$this->validator->technician($d);$r=$this->repo->createTechnician($d->toArray(),$a);$this->audit->log($a,'field.technician.created','technician',(int)$r['id'],null,$r);return$r;} public function update(int$id,TechnicianData$d,int$a):array{$this->validator->technician($d);$o=$this->get($id);$r=$this->repo->updateTechnician($id,$d->toArray(),$a);$this->audit->log($a,'field.technician.updated','technician',$id,$o,$r);return$r;} public function delete(int$id,int$a):void{$o=$this->get($id);$this->repo->deleteTechnician($id,$a);$this->audit->log($a,'field.technician.deleted','technician',$id,$o,null);}
 public function relations(int$id,string$r):array{$this->get($id);return$this->repo->technicianRelation($id,$r);} public function addRelation(int$id,string$r,array$d,int$a):array{$this->get($id);if($r==='skills'&&trim((string)($d['skill_name']??''))==='')throw new ValidationException([['code'=>'validation_error','detail'=>'Skill name is required.']]);if($r==='service-areas'&&(!trim((string)($d['city']??''))||!trim((string)($d['area']??''))))throw new ValidationException([['code'=>'validation_error','detail'=>'City and area are required.']]);if($r==='availability'&&strtotime((string)($d['ends_at']??''))<=strtotime((string)($d['starts_at']??'')))throw new ValidationException([['code'=>'validation_error','detail'=>'Availability end must follow its start.']]);$allowed=match($r){'skills'=>['skill_name','proficiency','certified_at','expires_at'],'service-areas'=>['city','area','is_primary'],'availability'=>['availability_type','starts_at','ends_at','reason'],default=>[]};$safe=array_intersect_key($d,array_flip($allowed));return$this->repo->saveTechnicianRelation($id,$r,$safe,$a);} public function updateRelation(int$id,string$r,int$row,array$d,int$a):array{$allowed=match($r){'skills'=>['skill_name','proficiency','certified_at','expires_at'],'service-areas'=>['city','area','is_primary'],'availability'=>['availability_type','starts_at','ends_at','reason'],default=>[]};return$this->repo->updateTechnicianRelation($id,$r,$row,array_intersect_key($d,array_flip($allowed)),$a);} public function deleteRelation(int$id,string$r,int$row,int$a):void{$this->repo->deleteTechnicianRelation($id,$r,$row,$a);}
 public function schedule(int$id,array$q):array{$q['technician_id']=$id;return$this->repo->schedule($q);}
}
