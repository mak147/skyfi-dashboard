<?php

declare(strict_types=1);

namespace SkyFi\Reports\Services;

use SkyFi\Reports\Contracts\ReportRepositoryContract;
use SkyFi\Reports\Contracts\ReportServiceContract;
use SkyFi\Reports\DTOs\ReportRequest;
use SkyFi\Reports\Validators\ReportRequestValidator;

final class ReportService implements ReportServiceContract
{
    public function __construct(private readonly ReportRepositoryContract $repository,private readonly ReportCatalog $catalog,private readonly ReportRequestValidator $validator) {}

    public function generate(ReportRequest $request, bool $export = false): array
    {
        $this->validator->validate($request);
        $definition=$this->catalog->get($request->reportKey);
        $result=$this->repository->run($request,$export);
        $numeric=array_values(array_filter($result['columns'],static fn(array $column):bool=>in_array($column['type'],['number','currency'],true)));
        $kpis=[['key'=>'records','label'=>'Records','value'=>$result['total'],'format'=>'number']];
        foreach (array_slice($numeric,0,3) as $column) {
            $values=array_map(static fn(array $row):float=>(float)($row[$column['key']]??0),$result['rows']);
            $kpis[]=['key'=>$column['key'],'label'=>$column['label'],'value'=>array_sum($values),'format'=>$column['type']];
        }
        $labelKey=$result['columns'][0]['key']??'label';
        $series=[];
        foreach (array_slice($numeric,0,3) as $column) $series[]=['key'=>$column['key'],'label'=>$column['label'],'data'=>array_map(static fn(array $row):float=>(float)($row[$column['key']]??0),$result['rows'])];
        $assumptions=[];
        if($request->reportKey==='customer.churn')$assumptions[]='Churn uses the current disconnected/archived status and its last update because historical customer status events are not available.';
        if($request->reportKey==='inventory.asset-depreciation')$assumptions[]='Placeholder: no approved useful-life or depreciation policy exists in operational data.';
        if(in_array($definition['category'],['billing','payment','finance','purchasing','vendor'],true))$assumptions[]='Currencies are reported separately; no foreign-exchange conversion is applied.';
        return ['report'=>[...$definition,'generated_at'=>(new \DateTimeImmutable())->format(DATE_ATOM),'filters'=>$request->filters,'columns'=>$result['columns'],'rows'=>$result['rows'],'kpis'=>$kpis,'visualizations'=>[['type'=>$definition['default_visualization'],'labels'=>array_map(static fn(array $row):string=>(string)($row[$labelKey]??''),$result['rows']),'series'=>$series]],'assumptions'=>$assumptions],'meta'=>['current_page'=>$export?1:$request->page,'per_page'=>$export?$result['total']:$request->perPage,'total'=>$result['total'],'last_page'=>$export?1:max(1,(int)ceil($result['total']/$request->perPage))]];
    }
}
