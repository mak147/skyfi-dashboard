<?php

declare(strict_types=1);

namespace SkyFi\Reports\Services;

use SkyFi\Reports\Repositories\PdoReportConfigurationRepository;
use SkyFi\Shared\Exceptions\ValidationException;

final class ReportConfigurationService
{
    public function __construct(private readonly PdoReportConfigurationRepository $repository,private readonly ReportCatalog $catalog) {}
    /** @return array<int,array<string,mixed>> */ public function saved(int $user):array{return$this->repository->saved($user);}
    /** @return array<string,mixed> */ public function savedOne(int$id,int$user):array{return$this->repository->savedOne($id,$user);}
    /** @param array<string,mixed>$data @return array<string,mixed> */ public function saveSaved(array$data,int$user,?int$id=null):array{$this->base($data);if(!in_array($data['visibility']??'private',['private','shared'],true))$this->invalid('Visibility must be private or shared.');return$this->repository->saveSaved($data,$user,$id);}
    public function deleteSaved(int$id,int$user):void{$this->repository->deleteSaved($id,$user);}
    /** @return array<int,array<string,mixed>> */ public function templates():array{return$this->repository->templates();}
    /** @return array<string,mixed> */ public function template(int$id):array{return$this->repository->template($id);}
    /** @param array<string,mixed>$data @return array<string,mixed> */ public function saveTemplate(array$data,int$user,?int$id=null):array{$this->base($data);if(trim((string)($data['code']??''))==='')$this->invalid('Template code is required.');return$this->repository->saveTemplate($data,$user,$id);}
    public function deleteTemplate(int$id):void{$this->repository->deleteTemplate($id);}
    /** @return array<int,array<string,mixed>> */ public function schedules(int$user):array{return$this->repository->schedules($user);}
    /** @return array<string,mixed> */ public function schedule(int$id,int$user):array{return$this->repository->schedule($id,$user);}
    /** @param array<string,mixed>$data @return array<string,mixed> */ public function saveSchedule(array$data,int$user,?int$id=null):array{if(trim((string)($data['name']??''))===''||(int)($data['saved_report_id']??0)<1)$this->invalid('Schedule name and saved report are required.');if(!in_array($data['frequency']??'',['daily','weekly','monthly','custom'],true)||!in_array($data['export_format']??'',['pdf','xlsx','csv'],true))$this->invalid('Schedule frequency or export format is invalid.');return$this->repository->saveSchedule($data,$user,$id);}
    public function deleteSchedule(int$id,int$user):void{$this->repository->deleteSchedule($id,$user);}
    private function base(array$data):void{if(trim((string)($data['name']??''))==='')$this->invalid('A report name is required.');$this->catalog->get((string)($data['report_key']??''));}
    private function invalid(string$message):never{throw new ValidationException([['code'=>'invalid_report_configuration','detail'=>$message]]);}
}
