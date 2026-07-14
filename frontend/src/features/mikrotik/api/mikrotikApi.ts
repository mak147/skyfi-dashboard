import { apiClient } from '@/lib/apiClient';

import type {
  ConnectionTestResult,
  MikrotikRouter,
  RouterDiscovery,
  RouterFormData,
  RouterGroup,
  RouterHealth,
  RouterListFilters,
  RouterListResponse,
  RouterTag,
} from '../types';

interface Resource<T> {
  data: { attributes: T };
}

const resource = <T>(response: Resource<T>): T => response.data.attributes;

export const getRouters = async (page: number, perPage: number, filters: RouterListFilters, sort: string): Promise<RouterListResponse> => {
  const params = new URLSearchParams({ 'page[number]': String(page), 'page[size]': String(perPage), sort });
  if (filters.search) params.set('filter[search]', filters.search);
  if (filters.router_group_id) params.set('filter[router_group_id]', filters.router_group_id);
  if (filters.tag_id) params.set('filter[tag_id]', filters.tag_id);
  if (filters.site) params.set('filter[site]', filters.site);
  if (filters.status) params.set('filter[status]', filters.status);
  if (filters.is_enabled) params.set('filter[is_enabled]', filters.is_enabled);

  return (await apiClient.get<RouterListResponse>(`/mikrotik/routers?${params.toString()}`)).data;
};

export const getRouter = async (id: number): Promise<MikrotikRouter> => resource((await apiClient.get<Resource<MikrotikRouter>>(`/mikrotik/routers/${id}`)).data);
export const createRouter = async (data: RouterFormData): Promise<MikrotikRouter> => resource((await apiClient.post<Resource<MikrotikRouter>>('/mikrotik/routers', data)).data);
export const updateRouter = async (id: number, data: RouterFormData): Promise<MikrotikRouter> => resource((await apiClient.put<Resource<MikrotikRouter>>(`/mikrotik/routers/${id}`, data)).data);
export const deleteRouter = async (id: number): Promise<void> => { await apiClient.delete(`/mikrotik/routers/${id}`); };
export const setRouterEnabled = async (id: number, isEnabled: boolean): Promise<MikrotikRouter> => resource((await apiClient.patch<Resource<MikrotikRouter>>(`/mikrotik/routers/${id}/${isEnabled ? 'enable' : 'disable'}`)).data);
export const testRouterConnection = async (data: Pick<RouterFormData, 'host' | 'api_port' | 'api_username'> & { api_password: string }): Promise<ConnectionTestResult> => resource((await apiClient.post<Resource<ConnectionTestResult>>('/mikrotik/test-connection', data)).data);
export const testSavedRouterConnection = async (id: number): Promise<ConnectionTestResult> => resource((await apiClient.post<Resource<ConnectionTestResult>>(`/mikrotik/routers/${id}/test-connection`)).data);
export const discoverRouter = async (id: number): Promise<RouterDiscovery> => resource((await apiClient.post<Resource<RouterDiscovery>>(`/mikrotik/routers/${id}/discover`)).data);
export const getRouterHealth = async (id: number): Promise<RouterHealth | null> => {
  const response = await apiClient.get<{ data: { attributes: RouterHealth } | null }>(`/mikrotik/routers/${id}/health`);
  return response.data.data?.attributes ?? null;
};
export const checkRouterHealth = async (id: number): Promise<RouterHealth> => resource((await apiClient.post<Resource<RouterHealth>>(`/mikrotik/routers/${id}/health/check`)).data);
export const getRouterGroups = async (): Promise<RouterGroup[]> => (await apiClient.get<{ data: Array<{ attributes: RouterGroup }> }>('/mikrotik/router-groups')).data.data.map((item) => item.attributes);
export const getRouterTags = async (): Promise<RouterTag[]> => (await apiClient.get<{ data: Array<{ attributes: RouterTag }> }>('/mikrotik/router-tags')).data.data.map((item) => item.attributes);
