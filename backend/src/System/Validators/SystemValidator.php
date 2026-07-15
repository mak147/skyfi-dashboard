<?php declare(strict_types=1);
namespace SkyFi\System\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
class SystemValidator
{
    public function company(array $d): void { $e=[]; $this->required($d,'company_name',$e); if (($d['email']??'')!=='' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e[]=$this->err('email','Email address is invalid.'); $this->throw($e); }
    public function branch(array $d): void { $e=[]; $this->required($d,'code',$e); $this->required($d,'name',$e); if (($d['email']??'')!=='' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e[]=$this->err('email','Email address is invalid.'); $this->status($d,$e); $this->throw($e); }
    public function department(array $d): void { $e=[]; $this->required($d,'code',$e); $this->required($d,'name',$e); $this->status($d,$e); $this->throw($e); }
    public function system(array $d): void { $e=[]; if (isset($d['session_timeout_minutes']) && ((int)$d['session_timeout_minutes'] < 5 || (int)$d['session_timeout_minutes'] > 1440)) $e[]=$this->err('session_timeout_minutes','Session timeout must be between 5 and 1440 minutes.'); $this->throw($e); }
    public function branding(array $d): void { $e=[]; foreach (['primary_color','secondary_color'] as $f) if (isset($d[$f]) && !preg_match('/^#[0-9a-fA-F]{6}$/',(string)$d[$f])) $e[]=$this->err($f,'Color must be a valid hex value.'); $this->throw($e); }
    public function localization(array $d): void { $e=[]; if (isset($d['first_day_of_week']) && ((int)$d['first_day_of_week']<0 || (int)$d['first_day_of_week']>6)) $e[]=$this->err('first_day_of_week','First day of week must be 0 through 6.'); $this->throw($e); }
    public function notifications(array $d): void { $this->throw([]); }
    public function asset(array $file, array $limits): void { $e=[]; if (($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK) $e[]=$this->err('file','The uploaded file could not be processed.'); $max=((int)($limits['max_mb']??5))*1024*1024; if (($file['size']??0)>$max) $e[]=$this->err('file','The uploaded file exceeds the configured limit.'); $allowed=$limits['allowed_mime_types']??[]; if ($allowed && isset($file['type']) && !in_array($file['type'],$allowed,true)) $e[]=$this->err('file','The uploaded file type is not allowed.'); $this->throw($e); }
    private function required(array $d,string $f,array &$e): void { if (trim((string)($d[$f]??''))==='') $e[]=$this->err($f,ucfirst(str_replace('_',' ',$f)).' is required.'); }
    private function status(array $d,array &$e): void { if (isset($d['status']) && !in_array($d['status'],['active','inactive'],true)) $e[]=$this->err('status','Status is invalid.'); }
    private function err(string $f,string $m): array { return ['code'=>'validation_error','detail'=>$m,'source'=>['pointer'=>'/data/attributes/'.$f]]; }
    private function throw(array $e): void { if ($e) throw new ValidationException($e); }
}
