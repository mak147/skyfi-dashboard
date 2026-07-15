import {apiClient} from '@/lib/apiClient';import type{Dashboard,InstallationFormValues,InstallationRequest,Page,Technician,WorkOrder,WorkOrderFormValues}from '../types';
const q=(v:Record<string,unknown>)=>{const p=new URLSearchParams();Object.entries(v).forEach(([k,x])=>{if(x!==undefined&&x!==null&&x!=='')p.set(k,String(x))});return p.toString()};const attrs=<T>(x:{data:{attributes:T}})=>x.data.attributes;
export const fieldServiceApi={
 dashboard:async()=>(await apiClient.get<{data:Dashboard}>('/field-service/dashboard')).data.data,
 requests:async(f:Record<string,unknown>={})=>(await apiClient.get<Page<InstallationRequest>>(`/field-service/installation-requests?${q(f)}`)).data,
 createRequest:async(d:InstallationFormValues)=>attrs((await apiClient.post<{data:{attributes:InstallationRequest}}>('/field-service/installation-requests',{...d,source:d.source??'manual'})).data),
 requestAction:async(id:number,a:string,d:Record<string,unknown>={})=>attrs((await apiClient.post<{data:{attributes:InstallationRequest|WorkOrder}}>(`/field-service/installation-requests/${id}/${a}`,d)).data),
 orders:async(f:Record<string,unknown>={})=>(await apiClient.get<Page<WorkOrder>>(`/field-service/work-orders?${q(f)}`)).data,
 order:async(id:number)=>attrs((await apiClient.get<{data:{attributes:WorkOrder}}>(`/field-service/work-orders/${id}`)).data),
 createOrder:async(d:WorkOrderFormValues)=>attrs((await apiClient.post<{data:{attributes:WorkOrder}}>('/field-service/work-orders',d)).data),
 updateOrder:async(id:number,d:WorkOrderFormValues)=>attrs((await apiClient.put<{data:{attributes:WorkOrder}}>(`/field-service/work-orders/${id}`,d)).data),
 orderAction:async(id:number,a:string,d:Record<string,unknown>={})=>attrs((await apiClient.request<{data:{attributes:WorkOrder}}>({method:a==='status'?'PATCH':'POST',url:`/field-service/work-orders/${id}/${a}`,data:d})).data),
 schedule:async(f:Record<string,unknown>={})=>(await apiClient.get<{data:WorkOrder[]}>(`/field-service/schedule?${q(f)}`)).data.data,
 technicians:async(f:Record<string,unknown>={})=>(await apiClient.get<Page<Technician>>(`/field-service/technicians?${q(f)}`)).data,
 technician:async(id:number)=>attrs((await apiClient.get<{data:{attributes:Technician}}>(`/field-service/technicians/${id}`)).data),
 technicianSchedule:async(id:number,f:Record<string,unknown>={})=>(await apiClient.get<{data:WorkOrder[]}>(`/field-service/technicians/${id}/schedule?${q(f)}`)).data.data,
 saveMaterial:async(wo:number,d:Record<string,unknown>)=>(await apiClient.post(`/field-service/work-orders/${wo}/materials`,d)).data.data,
 createVisit:async(wo:number,d:Record<string,unknown>)=>(await apiClient.post(`/field-service/work-orders/${wo}/visits`,d)).data.data,
 visitAction:async(wo:number,visit:number,a:string,d:Record<string,unknown>)=>(await apiClient.post(`/field-service/work-orders/${wo}/visits/${visit}/${a}`,d)).data.data,
 addLog:async(wo:number,d:Record<string,unknown>)=>(await apiClient.post(`/field-service/work-orders/${wo}/logs`,d)).data.data,
 lookup:async(r:string,search='')=>(await apiClient.get<{data:Array<Record<string,unknown>>}>(`/field-service/lookups/${r}?${q({search})}`)).data.data,
};
