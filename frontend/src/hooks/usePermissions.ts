import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/lib/apiClient';
import { useAuth } from './useAuth';
export const usePermissions=()=>{const {isAuthenticated}=useAuth();const q=useQuery({queryKey:['me','permissions'],queryFn:async()=> (await apiClient.get<{data:string[]}>('/me/permissions')).data.data,enabled:isAuthenticated,staleTime:300000});const can=(p:string)=>Boolean(q.data?.includes('*')||q.data?.includes(p));return {...q,can};};
