import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/apiClient';
import type {
  MikrotikPppProfile,
  PppoeAccount,
  PppoeAccountStatistics,
  PppoeActiveSession,
  PppoeListFilters,
  PppoeListResponse,
  PppoeSessionHistory,
  PppoeSyncDiscrepancy,
  PppoeSyncLog,
  PppoeSyncResult,
} from '../types';
import type {
  ChangePackageValues,
  EditPppoeFormValues,
  ImportUsersValues,
  PppoeFormValues,
  ResetPasswordValues,
} from '../schemas';

interface Resource<T> {
  data: { attributes: T };
}

const resource = <T>(response: Resource<T>): T => response.data.attributes;

export const getPppoeAccounts = async (filters: PppoeListFilters = {}): Promise<PppoeListResponse> => {
  const params = new URLSearchParams();
  if (filters.page) params.set('page[number]', String(filters.page));
  if (filters.perPage) params.set('page[size]', String(filters.perPage));
  if (filters.sort) params.set('sort', filters.sort);
  if (filters.search) params.set('filter[search]', filters.search);
  if (filters.customer_id) params.set('filter[customer_id]', String(filters.customer_id));
  if (filters.connection_id) params.set('filter[connection_id]', String(filters.connection_id));
  if (filters.package_id) params.set('filter[package_id]', String(filters.package_id));
  if (filters.router_id) params.set('filter[router_id]', String(filters.router_id));
  if (filters.status) params.set('filter[status]', filters.status);
  if (filters.sync_status) params.set('filter[sync_status]', filters.sync_status);

  return (await apiClient.get<PppoeListResponse>(`/pppoe/accounts?${params.toString()}`)).data;
};

export const getPppoeAccount = async (id: number): Promise<PppoeAccount> =>
  resource((await apiClient.get<Resource<PppoeAccount>>(`/pppoe/accounts/${id}`)).data);

export const createPppoeAccount = async (data: Partial<PppoeFormValues>): Promise<PppoeAccount> =>
  resource((await apiClient.post<Resource<PppoeAccount>>('/pppoe/accounts', data)).data);

export const updatePppoeAccount = async (id: number, data: Partial<EditPppoeFormValues>): Promise<PppoeAccount> =>
  resource((await apiClient.put<Resource<PppoeAccount>>(`/pppoe/accounts/${id}`, data)).data);

export const deletePppoeAccount = async (id: number): Promise<void> => {
  await apiClient.delete(`/pppoe/accounts/${id}`);
};

export const setPppoeAccountEnabled = async (id: number, enable: boolean): Promise<PppoeAccount> =>
  resource((await apiClient.patch<Resource<PppoeAccount>>(`/pppoe/accounts/${id}/${enable ? 'enable' : 'disable'}`)).data);

export const suspendPppoeAccount = async (id: number): Promise<PppoeAccount> =>
  resource((await apiClient.post<Resource<PppoeAccount>>(`/pppoe/accounts/${id}/suspend`)).data);

export const resumePppoeAccount = async (id: number): Promise<PppoeAccount> =>
  resource((await apiClient.post<Resource<PppoeAccount>>(`/pppoe/accounts/${id}/resume`)).data);

export const reconnectPppoeAccount = async (id: number): Promise<void> => {
  await apiClient.post(`/pppoe/accounts/${id}/reconnect`);
};

export const resetPppoePassword = async (id: number, data: ResetPasswordValues): Promise<PppoeAccount> =>
  resource((await apiClient.post<Resource<PppoeAccount>>(`/pppoe/accounts/${id}/reset-password`, data)).data);

export const changePppoePackage = async (id: number, data: ChangePackageValues): Promise<PppoeAccount> =>
  resource((await apiClient.put<Resource<PppoeAccount>>(`/pppoe/accounts/${id}/package`, data)).data);

export const getActiveSessions = async (routerId?: number): Promise<PppoeActiveSession[]> => {
  const params = new URLSearchParams();
  if (routerId) params.set('router_id', String(routerId));
  const res = await apiClient.get<{ data: Array<{ attributes: PppoeActiveSession }> }>(`/pppoe/sessions/active?${params.toString()}`);
  return res.data.data.map((item) => item.attributes);
};

export const disconnectActiveSession = async (routerId: number, sessionIdOrUsername: string): Promise<void> => {
  await apiClient.post('/pppoe/sessions/active/disconnect', { router_id: routerId, session_id: sessionIdOrUsername });
};

export const getSessionHistory = async (page = 1, perPage = 15, accountId?: number, routerId?: number, username?: string): Promise<{ items: PppoeSessionHistory[]; total: number; lastPage: number }> => {
  const params = new URLSearchParams({ 'page[number]': String(page), 'page[size]': String(perPage) });
  if (accountId) params.set('filter[account_id]', String(accountId));
  if (routerId) params.set('filter[router_id]', String(routerId));
  if (username) params.set('filter[username]', username);

  const res = await apiClient.get<{ data: Array<{ attributes: PppoeSessionHistory }>; meta: { total: number; last_page: number } }>(`/pppoe/sessions/history?${params.toString()}`);
  return {
    items: res.data.data.map((item) => item.attributes),
    total: res.data.meta.total,
    lastPage: res.data.meta.last_page,
  };
};

export const getAccountStatistics = async (accountId: number): Promise<PppoeAccountStatistics> =>
  resource((await apiClient.get<Resource<PppoeAccountStatistics>>(`/pppoe/accounts/${accountId}/statistics`)).data);

export const getRouterProfiles = async (routerId: number): Promise<MikrotikPppProfile[]> => {
  if (!routerId || routerId <= 0) return [];
  const res = await apiClient.get<{ data: Array<{ attributes: MikrotikPppProfile }> }>(`/pppoe/routers/${routerId}/profiles`);
  return res.data.data.map((item) => item.attributes);
};

export const syncRouterPppoe = async (routerId: number): Promise<PppoeSyncResult> =>
  resource((await apiClient.post<Resource<PppoeSyncResult>>(`/pppoe/sync/router/${routerId}`)).data);

export const syncAccountPppoe = async (accountId: number): Promise<PppoeSyncResult> =>
  resource((await apiClient.post<Resource<PppoeSyncResult>>(`/pppoe/sync/account/${accountId}`)).data);

export const detectMissingPppoe = async (routerId?: number): Promise<PppoeSyncDiscrepancy[]> => {
  const params = routerId ? `?router_id=${routerId}` : '';
  const res = await apiClient.post<{ data: Array<{ attributes: PppoeSyncDiscrepancy }> }>(`/pppoe/sync/detect-missing${params}`);
  return res.data.data.map((item) => item.attributes);
};

export const repairPppoeSync = async (options: Record<string, unknown>): Promise<{ repaired_count: number; failed_count: number; logs: string[] }> =>
  resource((await apiClient.post<Resource<{ repaired_count: number; failed_count: number; logs: string[] }>>('/pppoe/sync/repair', options)).data);

export const importPppoeUsers = async (data: Partial<ImportUsersValues>): Promise<{ imported_count: number; failed_count: number; errors: string[] }> =>
  resource((await apiClient.post<Resource<{ imported_count: number; failed_count: number; errors: string[] }>>('/pppoe/sync/import', data)).data);

export const getPppoeSyncLogs = async (limit = 50, routerId?: number, accountId?: number): Promise<PppoeSyncLog[]> => {
  const params = new URLSearchParams({ limit: String(limit) });
  if (routerId) params.set('router_id', String(routerId));
  if (accountId) params.set('account_id', String(accountId));
  const res = await apiClient.get<{ data: Array<{ attributes: PppoeSyncLog }> }>(`/pppoe/sync/logs?${params.toString()}`);
  return res.data.data.map((item) => item.attributes);
};

// React Query Hooks
export const usePppoeAccounts = (filters: PppoeListFilters = {}) =>
  useQuery({
    queryKey: ['pppoe', 'accounts', filters],
    queryFn: () => getPppoeAccounts(filters),
  });

export const usePppoeAccount = (id: number) =>
  useQuery({
    queryKey: ['pppoe', 'account', id],
    queryFn: () => getPppoeAccount(id),
    enabled: id > 0,
  });

export const useActiveSessions = (routerId?: number) =>
  useQuery({
    queryKey: ['pppoe', 'active-sessions', routerId],
    queryFn: () => getActiveSessions(routerId),
    refetchInterval: 15000, // Live poll every 15s
  });

export const useSessionHistory = (page = 1, perPage = 15, accountId?: number, routerId?: number, username?: string) =>
  useQuery({
    queryKey: ['pppoe', 'session-history', page, perPage, accountId, routerId, username],
    queryFn: () => getSessionHistory(page, perPage, accountId, routerId, username),
  });

export const useAccountStatistics = (accountId: number) =>
  useQuery({
    queryKey: ['pppoe', 'statistics', accountId],
    queryFn: () => getAccountStatistics(accountId),
    enabled: accountId > 0,
  });

export const useRouterProfiles = (routerId: number) =>
  useQuery({
    queryKey: ['pppoe', 'router-profiles', routerId],
    queryFn: () => getRouterProfiles(routerId),
    enabled: routerId > 0,
  });

export const usePppoeSyncLogs = (limit = 50, routerId?: number, accountId?: number) =>
  useQuery({
    queryKey: ['pppoe', 'sync-logs', limit, routerId, accountId],
    queryFn: () => getPppoeSyncLogs(limit, routerId, accountId),
  });

export const useCreatePppoeAccount = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createPppoeAccount,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe'] }),
  });
};

export const useUpdatePppoeAccount = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<EditPppoeFormValues> }) => updatePppoeAccount(id, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['pppoe'] });
      queryClient.invalidateQueries({ queryKey: ['pppoe', 'account', variables.id] });
    },
  });
};

export const useDeletePppoeAccount = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: deletePppoeAccount,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe'] }),
  });
};
