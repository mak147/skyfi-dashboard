<?php

declare(strict_types=1);

namespace SkyFi\Reports\Repositories;

use PDO;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoReportConfigurationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return array<int,array<string,mixed>> */
    public function saved(int $userId):array{return $this->all("SELECT * FROM saved_reports WHERE deleted_at IS NULL AND (owner_user_id=? OR visibility='shared') ORDER BY updated_at DESC",[$userId]);}
    /** @return array<string,mixed> */
    public function savedOne(int $id,int $userId):array{return $this->one("SELECT * FROM saved_reports WHERE id=? AND deleted_at IS NULL AND (owner_user_id=? OR visibility='shared')",[$id,$userId]);}
    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function saveSaved(array $data,int $userId,?int $id=null):array
    {
        $values=[$data['report_template_id']??null,trim((string)($data['name']??'')),$data['description']??null,(string)($data['report_key']??''),$this->json($data['filters']??[]),$this->json($data['selected_columns']??[]),$this->json($data['visualization']??[]),$data['visibility']??'private'];
        if($id===null){$sql='INSERT INTO saved_reports(owner_user_id,report_template_id,name,description,report_key,filters,selected_columns,visualization,visibility) VALUES(?,?,?,?,?,?,?,?,?)';$this->pdo->prepare($sql)->execute([$userId,...$values]);$id=(int)$this->pdo->lastInsertId();}
        else{$this->owned('saved_reports',$id,$userId);$this->pdo->prepare('UPDATE saved_reports SET report_template_id=?,name=?,description=?,report_key=?,filters=?,selected_columns=?,visualization=?,visibility=? WHERE id=?')->execute([...$values,$id]);}
        return $this->savedOne($id,$userId);
    }
    public function deleteSaved(int $id,int $userId):void{$this->owned('saved_reports',$id,$userId);$this->pdo->prepare('UPDATE saved_reports SET deleted_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$id]);}

    /** @return array<int,array<string,mixed>> */
    public function templates():array{return $this->all("SELECT * FROM report_templates WHERE deleted_at IS NULL AND status='active' ORDER BY category,name");}
    /** @return array<string,mixed> */
    public function template(int $id):array{return $this->one('SELECT * FROM report_templates WHERE id=? AND deleted_at IS NULL',[$id]);}
    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function saveTemplate(array $data,int $userId,?int $id=null):array
    {
        $values=[trim((string)($data['code']??'')),trim((string)($data['name']??'')),(string)($data['category']??''),(string)($data['report_key']??''),$data['description']??null,$this->json($data['default_filters']??[]),$this->json($data['default_columns']??[]),$this->json($data['visualization']??[]),$data['status']??'active'];
        if($id===null){$this->pdo->prepare('INSERT INTO report_templates(code,name,category,report_key,description,default_filters,default_columns,visualization,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([...$values,$userId]);$id=(int)$this->pdo->lastInsertId();}
        else{$existing=$this->template($id);if((int)$existing['is_system']===1)throw new \DomainException('System templates cannot be modified.');$this->pdo->prepare('UPDATE report_templates SET code=?,name=?,category=?,report_key=?,description=?,default_filters=?,default_columns=?,visualization=?,status=?,updated_by=? WHERE id=?')->execute([...$values,$userId,$id]);}
        return $this->template($id);
    }
    public function deleteTemplate(int $id):void{$item=$this->template($id);if((int)$item['is_system']===1)throw new \DomainException('System templates cannot be deleted.');$this->pdo->prepare('UPDATE report_templates SET deleted_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$id]);}

    /** @return array<int,array<string,mixed>> */
    public function schedules(int $userId):array{return $this->all('SELECT * FROM scheduled_reports WHERE owner_user_id=? AND deleted_at IS NULL ORDER BY updated_at DESC',[$userId]);}
    /** @return array<string,mixed> */
    public function schedule(int $id,int $userId):array{return $this->one('SELECT * FROM scheduled_reports WHERE id=? AND owner_user_id=? AND deleted_at IS NULL',[$id,$userId]);}
    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function saveSchedule(array $data,int $userId,?int $id=null):array
    {
        $values=[(int)($data['saved_report_id']??0),trim((string)($data['name']??'')),$data['frequency']??'monthly',$data['schedule_expression']??null,$data['timezone']??'Asia/Karachi',$data['export_format']??'pdf',$this->json($data['recipients']??[]),$this->json($data['delivery_config']??[]),$data['status']??'draft',$data['next_run_at']??null];
        if($id===null){$this->pdo->prepare('INSERT INTO scheduled_reports(owner_user_id,saved_report_id,name,frequency,schedule_expression,timezone,export_format,recipients,delivery_config,status,next_run_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$userId,...$values]);$id=(int)$this->pdo->lastInsertId();}
        else{$this->schedule($id,$userId);$this->pdo->prepare('UPDATE scheduled_reports SET saved_report_id=?,name=?,frequency=?,schedule_expression=?,timezone=?,export_format=?,recipients=?,delivery_config=?,status=?,next_run_at=? WHERE id=?')->execute([...$values,$id]);}
        return $this->schedule($id,$userId);
    }
    public function deleteSchedule(int $id,int $userId):void{$this->schedule($id,$userId);$this->pdo->prepare('UPDATE scheduled_reports SET deleted_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$id]);}

    /** @return array<int,array<string,mixed>> */
    public function exports(int $userId):array{return $this->all('SELECT * FROM report_export_history WHERE requested_by=? ORDER BY created_at DESC LIMIT 200',[$userId]);}
    /** @return array<string,mixed> */
    public function export(int $id,int $userId):array{return $this->one('SELECT * FROM report_export_history WHERE id=? AND requested_by=?',[$id,$userId]);}
    /** @param array<string,mixed> $filters */
    public function createExport(int $userId,string $key,string $format,array $filters,?int $savedId):int{$this->pdo->prepare("INSERT INTO report_export_history(requested_by,saved_report_id,report_key,format,filters,status) VALUES(?,?,?,?,?,'processing')")->execute([$userId,$savedId,$key,$format,$this->json($filters)]);return(int)$this->pdo->lastInsertId();}
    public function completeExport(int $id,string $name,string $path,string $mime,int $rows,int $size):void{$this->pdo->prepare("UPDATE report_export_history SET status='completed',file_name=?,file_path=?,mime_type=?,row_count=?,file_size=?,completed_at=NOW(),expires_at=DATE_ADD(NOW(),INTERVAL 7 DAY) WHERE id=?")->execute([$name,$path,$mime,$rows,$size,$id]);}
    public function failExport(int $id,string $message):void{$this->pdo->prepare("UPDATE report_export_history SET status='failed',error_message=? WHERE id=?")->execute([substr($message,0,1000),$id]);}
    public function deleteExport(int $id,int $userId):void{$item=$this->export($id,$userId);if($item['file_path']&&is_file($item['file_path']))@unlink($item['file_path']);$this->pdo->prepare('DELETE FROM report_export_history WHERE id=?')->execute([$id]);}

    /** @param array<int,mixed> $params @return array<int,array<string,mixed>> */
    private function all(string $sql,array $params=[]):array{$s=$this->pdo->prepare($sql);$s->execute($params);$rows=$s->fetchAll()?:[];return array_map([$this,'decode'],$rows);}
    /** @param array<int,mixed> $params @return array<string,mixed> */
    private function one(string $sql,array $params=[]):array{$rows=$this->all($sql,$params);return$rows[0]??throw new NotFoundException('Report resource not found.');}
    private function owned(string $table,int $id,int $userId):void{$s=$this->pdo->prepare("SELECT id FROM {$table} WHERE id=? AND owner_user_id=? AND deleted_at IS NULL");$s->execute([$id,$userId]);if(!$s->fetchColumn())throw new NotFoundException('Report resource not found.');}
    /** @param mixed $value */ private function json($value):string{return json_encode($value,JSON_THROW_ON_ERROR);}
    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function decode(array $row):array{foreach(['filters','selected_columns','visualization','default_filters','default_columns','recipients','delivery_config']as$key)if(isset($row[$key])&&is_string($row[$key]))$row[$key]=json_decode($row[$key],true)??[];return$row;}
}
