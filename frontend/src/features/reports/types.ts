export type ReportValue = string | number | null;
export interface ReportDefinition { key:string; name:string; category:string; description:string; default_visualization:'line'|'bar'|'pie'|'table'; filters:string[]; is_placeholder:boolean; }
export interface ReportCatalogGroup { category:string; label:string; reports:ReportDefinition[]; }
export interface ReportColumn { key:string; label:string; type:'string'|'number'|'currency'|'date'; }
export interface Kpi { key:string; label:string; value:number; format:'number'|'currency'; }
export interface ChartSeries { key:string; label:string; data:number[]; }
export interface Visualization { type:string; labels:string[]; series:ChartSeries[]; }
export interface ReportResult extends Omit<ReportDefinition,'filters'> { generated_at:string; filters:ReportFilters; columns:ReportColumn[]; rows:Array<Record<string,ReportValue>>; kpis:Kpi[]; visualizations:Visualization[]; assumptions:string[]; }
export interface PaginationMeta { current_page:number; per_page:number; total:number; last_page:number; }
export type ReportFilters = Partial<Record<'date_from'|'date_to'|'customer_id'|'region'|'pop_site_id'|'tower_id'|'package_id'|'technician_id'|'supplier_id'|'warehouse_id'|'status',string>>;
export interface FilterOption { id:number|string; label:string; }
export interface FilterOptions { customers:FilterOption[]; regions:FilterOption[]; pop_sites:FilterOption[]; towers:FilterOption[]; packages:FilterOption[]; technicians:FilterOption[]; suppliers:FilterOption[]; warehouses:FilterOption[]; }
export interface DashboardWidget { report_key:string; title:string; kpis:Kpi[]; visualizations:Visualization[]; rows:Array<Record<string,ReportValue>>; drill_down:{path:string;report_key:string}; }
export interface ReportDashboard { key:string; name:string; generated_at:string; widgets:DashboardWidget[]; }
export interface SavedReport { id:number; owner_user_id:number; name:string; description:string|null; report_key:string; filters:ReportFilters; selected_columns:string[]; visualization:Record<string,unknown>; visibility:'private'|'shared'; updated_at:string; }
export interface ScheduledReport { id:number; name:string; saved_report_id:number; frequency:string; export_format:string; status:string; next_run_at:string|null; updated_at:string; }
export interface ExportHistory { id:number; report_key:string; format:'pdf'|'xlsx'|'csv'; status:'pending'|'processing'|'completed'|'failed'; file_name:string|null; row_count:number; file_size:number; error_message:string|null; created_at:string; }
