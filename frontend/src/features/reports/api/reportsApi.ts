import { apiClient } from '@/lib/apiClient';
import type { ExportHistory,FilterOptions,PaginationMeta,ReportCatalogGroup,ReportDashboard,ReportFilters,ReportResult,SavedReport,ScheduledReport } from '../types';
export const reportsApi={
 catalog:async()=>(await apiClient.get<{data:ReportCatalogGroup[]}>('/reports/catalog')).data.data,
 filters:async()=>(await apiClient.get<{data:FilterOptions}>('/reports/filters')).data.data,
 generate:async(reportKey:string,filters:ReportFilters,page=1,perPage=25)=>{const r=await apiClient.post<{data:ReportResult;meta:PaginationMeta}>('/reports/generate',{report_key:reportKey,filters,page,per_page:perPage});return r.data;},
 dashboard:async(key:string,filters:ReportFilters={})=>(await apiClient.get<{data:ReportDashboard}>(`/reports/dashboards/${key}`,{params:{filter:filters}})).data.data,
 saved:async()=>(await apiClient.get<{data:SavedReport[]}>('/reports/saved')).data.data,
 save:async(data:Partial<SavedReport>)=>(await apiClient.post<{data:SavedReport}>('/reports/saved',data)).data.data,
 updateSaved:async(id:number,data:Partial<SavedReport>)=>(await apiClient.put<{data:SavedReport}>(`/reports/saved/${id}`,data)).data.data,
 deleteSaved:async(id:number)=>{await apiClient.delete(`/reports/saved/${id}`);},
 schedules:async()=>(await apiClient.get<{data:ScheduledReport[]}>('/reports/schedules')).data.data,
 exports:async()=>(await apiClient.get<{data:ExportHistory[]}>('/reports/exports')).data.data,
 createExport:async(reportKey:string,format:string,filters:ReportFilters)=>(await apiClient.post<{data:ExportHistory}>('/reports/exports',{report_key:reportKey,format,filters})).data.data,
 deleteExport:async(id:number)=>{await apiClient.delete(`/reports/exports/${id}`);},
 download:async(id:number,fileName:string)=>{const response=await apiClient.get<Blob>(`/reports/exports/${id}/download`,{responseType:'blob'});const url=URL.createObjectURL(response.data);const anchor=document.createElement('a');anchor.href=url;anchor.download=fileName;anchor.click();URL.revokeObjectURL(url);},
};
