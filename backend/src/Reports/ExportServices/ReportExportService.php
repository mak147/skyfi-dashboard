<?php

declare(strict_types=1);

namespace SkyFi\Reports\ExportServices;

use SkyFi\Reports\DTOs\ReportRequest;
use SkyFi\Reports\Repositories\PdoReportConfigurationRepository;
use SkyFi\Reports\Services\ReportService;
use SkyFi\Shared\Exceptions\ValidationException;

final class ReportExportService
{
    public function __construct(private readonly ReportService $reports,private readonly PdoReportConfigurationRepository $repository,private readonly string $directory) {}

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function create(array$data,int$userId):array
    {
        $format=strtolower((string)($data['format']??''));if(!in_array($format,['csv','xlsx','pdf'],true))throw new ValidationException([['code'=>'invalid_export_format','detail'=>'Export format must be csv, xlsx, or pdf.']]);
        $request=ReportRequest::fromArray($data);$id=$this->repository->createExport($userId,$request->reportKey,$format,$request->filters,isset($data['saved_report_id'])?(int)$data['saved_report_id']:null);
        try{
            $result=$this->reports->generate($request,true);$safe=preg_replace('/[^a-z0-9-]+/','-',strtolower($request->reportKey))?:'report';$name=$safe.'-'.gmdate('Ymd-His').'.'.$format;
            if(!is_dir($this->directory)&&!mkdir($this->directory,0770,true)&&!is_dir($this->directory))throw new \RuntimeException('Unable to create export storage.');
            $path=rtrim($this->directory,'/').'/'.$userId.'-'.$id.'-'.$name;
            $mime=match($format){'csv'=>'text/csv','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','pdf'=>'application/pdf'};
            if ($format === 'csv') $this->csv($path, $result);
            elseif ($format === 'xlsx') $this->xlsx($path, $result);
            else $this->pdf($path, $result);
            $this->repository->completeExport($id,$name,$path,$mime,count($result['report']['rows']),(int)filesize($path));
        }catch(\Throwable$e){$this->repository->failExport($id,$e->getMessage());throw$e;}
        return$this->repository->export($id,$userId);
    }
    /** @return array<int,array<string,mixed>> */public function history(int$user):array{return$this->repository->exports($user);}
    /** @return array<string,mixed> */public function item(int$id,int$user):array{return$this->repository->export($id,$user);}
    public function delete(int$id,int$user):void{$this->repository->deleteExport($id,$user);}

    /** @param array<string,mixed>$report */
    private function csv(string$path,array$report):void{$f=fopen($path,'wb');if(!$f)throw new \RuntimeException('Unable to write CSV.');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,array_column($report['report']['columns'],'label'));foreach($report['report']['rows']as$row){$values=[];foreach($report['report']['columns']as$column){$v=(string)($row[$column['key']]??'');if(preg_match('/^[=+\-@]/',$v))$v="'".$v;$values[]=$v;}fputcsv($f,$values);}fclose($f);}
    /** @param array<string,mixed>$report */
    private function xlsx(string$path,array$report):void
    {
        if(!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class))throw new \RuntimeException('PhpSpreadsheet is required for XLSX exports. Run composer install.');
        $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle('Report');$sheet->setCellValue('A1',$report['report']['name']);$sheet->setCellValue('A2','Generated '.$report['report']['generated_at']);$row=4;$col=1;
        foreach($report['report']['columns']as$column){$sheet->setCellValue([$col++,$row],$column['label']);}$sheet->getStyle('A4:'.$sheet->getHighestColumn().'4')->getFont()->setBold(true);$row++;
        foreach($report['report']['rows']as$item){$col=1;foreach($report['report']['columns']as$column){$value=$item[$column['key']]??'';$sheet->setCellValue([$col++,$row],$value);}$row++;}foreach(range('A',$sheet->getHighestColumn())as$column)$sheet->getColumnDimension($column)->setAutoSize(true);(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);$book->disconnectWorksheets();
    }
    /** @param array<string,mixed>$report */
    private function pdf(string$path,array$report):void
    {
        if(!class_exists(\Dompdf\Dompdf::class))throw new \RuntimeException('Dompdf is required for PDF exports. Run composer install.');$esc=static fn(mixed$v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$html='<style>body{font:11px sans-serif;color:#172033}h1{color:#4338ca}table{border-collapse:collapse;width:100%}th,td{border:1px solid #cbd5e1;padding:5px;text-align:left}th{background:#eef2ff}</style><h1>'.$esc($report['report']['name']).'</h1><p>Generated '.$esc($report['report']['generated_at']).'</p><table><thead><tr>';foreach($report['report']['columns']as$column)$html.='<th>'.$esc($column['label']).'</th>';$html.='</tr></thead><tbody>';foreach($report['report']['rows']as$row){$html.='<tr>';foreach($report['report']['columns']as$column)$html.='<td>'.$esc($row[$column['key']]??'').'</td>';$html.='</tr>';}$html.='</tbody></table>';$pdf=new \Dompdf\Dompdf();$pdf->loadHtml($html);$pdf->setPaper('A4',count($report['report']['columns'])>7?'landscape':'portrait');$pdf->render();file_put_contents($path,$pdf->output());
    }
}
