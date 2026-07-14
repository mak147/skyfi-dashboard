import { apiClient } from '@/lib/apiClient';
import type { InternetPackage,PackageFilters,PackageFormData,PackageListResponse,PackageStatisticsData } from '../types';
interface One {data:{attributes:InternetPackage}}
export async function getPackages(page:number,size:number,filters:PackageFilters,sort:string){const p=new URLSearchParams({'page[number]':String(page),'page[size]':String(size),sort});Object.entries(filters).forEach(([k,v])=>{if(v)p.set(`filter[${k}]`,v)});return (await apiClient.get<PackageListResponse>(`/packages?${p}`)).data}
export async function getPackage(id:number){return (await apiClient.get<One>(`/packages/${id}`)).data.data.attributes}
export async function createPackage(data:PackageFormData){return (await apiClient.post<One>('/packages',data)).data.data.attributes}
export async function updatePackage(id:number,data:PackageFormData){return (await apiClient.put<One>(`/packages/${id}`,data)).data.data.attributes}
export async function deletePackage(id:number){await apiClient.delete(`/packages/${id}`)}
export async function changePackageStatus(id:number,status:string){return (await apiClient.patch<One>(`/packages/${id}/status`,{status})).data.data.attributes}
export async function duplicatePackage(id:number,data:{name?:string;code?:string}){return (await apiClient.post<One>(`/packages/${id}/duplicate`,data)).data.data.attributes}
export async function bulkStatus(ids:number[],status:string){return (await apiClient.patch('/packages/bulk/status',{ids,status})).data}
export async function bulkDelete(ids:number[]){return (await apiClient.delete('/packages/bulk',{data:{ids}})).data}
export async function getPackageStatistics(){return (await apiClient.get<{data:PackageStatisticsData}>('/packages/statistics')).data.data}
export async function getPackageActivity(id:number){return (await apiClient.get<{data:Array<Record<string,unknown>>}>(`/packages/${id}/activity`)).data.data}
export async function exportPackages(){const r=await apiClient.get('/packages/export',{responseType:'blob'});const u=URL.createObjectURL(r.data);const a=document.createElement('a');a.href=u;a.download='skyfi-packages.csv';a.click();URL.revokeObjectURL(u)}
