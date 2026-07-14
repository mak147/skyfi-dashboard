import {apiClient} from '@/lib/apiClient';import type{Payment,PaymentAccount,PaymentFilters,PaymentFormData,PaymentListResponse,PaymentMethod,PaymentStatisticsData,AllocationInput}from'../types';
const unwrap=(r:{data:{attributes:Payment}})=>r.data.attributes;
export async function getPayments(page=1,size=15,f:PaymentFilters={},sort='-payment_date'){const p=new URLSearchParams({'page[number]':String(page),'page[size]':String(size),sort});Object.entries(f).forEach(([k,v])=>{if(v)p.set(`filter[${k}]`,v)});return(await apiClient.get<PaymentListResponse>(`/payments?${p}`)).data}
export async function getPayment(id:number){return unwrap((await apiClient.get<{data:{attributes:Payment}}>(`/payments/${id}`)).data)}
export async function receivePayment(data:PaymentFormData){return unwrap((await apiClient.post<{data:{attributes:Payment}}>('/payments/receive',data)).data)}
export async function updatePayment(id:number,data:PaymentFormData){return unwrap((await apiClient.put<{data:{attributes:Payment}}>(`/payments/${id}`,data)).data)}
export async function deletePayment(id:number){await apiClient.delete(`/payments/${id}`)}
export async function allocatePayment(id:number,allocations:AllocationInput[]){return unwrap((await apiClient.post<{data:{attributes:Payment}}>(`/payments/${id}/allocate`,{allocations})).data)}
export async function reversePayment(id:number,reason:string){return unwrap((await apiClient.post<{data:{attributes:Payment}}>(`/payments/${id}/reverse`,{reason})).data)}
export async function refundPayment(id:number,data:{amount:string;reason:string;notes?:string;reference_number?:string}){return unwrap((await apiClient.post<{data:{attributes:Payment}}>(`/payments/${id}/refund`,data)).data)}
export async function getPaymentLookups(){return(await apiClient.get<{data:{methods:PaymentMethod[];accounts:PaymentAccount[]}}>('/payments/lookups')).data.data}
export async function getPaymentStatistics(){return(await apiClient.get<{data:PaymentStatisticsData}>('/payments/statistics')).data.data}
export async function exportPayments(filters:PaymentFilters={}){const p=new URLSearchParams();Object.entries(filters).forEach(([k,v])=>{if(v)p.set(`filter[${k}]`,v)});const response=await apiClient.get(`/payments/export?${p}`,{responseType:'blob'});const url=URL.createObjectURL(response.data as Blob);const a=document.createElement('a');a.href=url;a.download='skyfi-payments.csv';a.click();URL.revokeObjectURL(url)}
