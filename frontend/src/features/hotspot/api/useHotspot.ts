import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/apiClient';
import type {
  GenerateVoucherBatchValues,
  EditHotspotUserFormValues,
  HotspotProfileFormValues,
  HotspotUserFormValues,
  ImportHotspotUsersValues,
  ResetPasswordValues,
} from '../schemas';
import type {
  HotspotActiveSession,
  HotspotProfile,
  HotspotSessionHistory,
  HotspotSyncLog,
  HotspotSyncResult,
  HotspotUser,
  HotspotUserListFilters,
  HotspotUserListResponse,
  HotspotUserStatistics,
  MikrotikHotspotProfile,
  Voucher,
  VoucherBatch,
  VoucherStats,
} from '../types';

interface Resource<T> {
  data: { attributes: T };
}

const resource = <T>(response: Resource<T>): T => response.data.attributes;

// ─── Hotspot Users ────────────────────────────────────────────────────────────

export const getHotspotUsers = async (filters: HotspotUserListFilters = {}): Promise<HotspotUserListResponse> => {
  const params = new URLSearchParams();
  if (filters.page) params.set('page[number]', String(filters.page));
  if (filters.perPage) params.set('page[size]', String(filters.perPage));
  if (filters.sort) params.set('sort', filters.sort);
  if (filters.search) params.set('filter[search]', filters.search);
  if (filters.customer_id) params.set('filter[customer_id]', String(filters.customer_id));
  if (filters.router_id) params.set('filter[router_id]', String(filters.router_id));
  if (filters.profile_id) params.set('filter[profile_id]', String(filters.profile_id));
  if (filters.status) params.set('filter[status]', filters.status);
  if (filters.sync_status) params.set('filter[sync_status]', filters.sync_status);

  return (await apiClient.get<HotspotUserListResponse>(`/hotspot/users?${params.toString()}`)).data;
};

export const getHotspotUser = async (id: number): Promise<HotspotUser> =>
  resource((await apiClient.get<Resource<HotspotUser>>(`/hotspot/users/${id}`)).data);

export const createHotspotUser = async (data: Partial<HotspotUserFormValues>): Promise<HotspotUser> =>
  resource((await apiClient.post<Resource<HotspotUser>>('/hotspot/users', data)).data);

export const updateHotspotUser = async (id: number, data: Partial<EditHotspotUserFormValues>): Promise<HotspotUser> =>
  resource((await apiClient.put<Resource<HotspotUser>>(`/hotspot/users/${id}`, data)).data);

export const deleteHotspotUser = async (id: number): Promise<void> => {
  await apiClient.delete(`/hotspot/users/${id}`);
};

export const setHotspotUserEnabled = async (id: number, enable: boolean): Promise<HotspotUser> =>
  resource((await apiClient.patch<Resource<HotspotUser>>(`/hotspot/users/${id}/${enable ? 'enable' : 'disable'}`)).data);

export const suspendHotspotUser = async (id: number): Promise<HotspotUser> =>
  resource((await apiClient.post<Resource<HotspotUser>>(`/hotspot/users/${id}/suspend`)).data);

export const resumeHotspotUser = async (id: number): Promise<HotspotUser> =>
  resource((await apiClient.post<Resource<HotspotUser>>(`/hotspot/users/${id}/resume`)).data);

export const resetHotspotPassword = async (id: number, data: ResetPasswordValues): Promise<HotspotUser> =>
  resource((await apiClient.post<Resource<HotspotUser>>(`/hotspot/users/${id}/reset-password`, data)).data);

export const bulkImportHotspotUsers = async (data: Record<string, unknown>): Promise<{ imported_count: number; failed_count: number; errors: string[] }> =>
  resource((await apiClient.post<Resource<{ imported_count: number; failed_count: number; errors: string[] }>>('/hotspot/users/bulk-import', data)).data);

// ─── Hotspot Profiles ─────────────────────────────────────────────────────────

export const getHotspotProfiles = async (page = 1, perPage = 15, search?: string, routerId?: number): Promise<{ data: Array<{ attributes: HotspotProfile }>; meta: { total: number; last_page: number; current_page: number } }> => {
  const params = new URLSearchParams({ 'page[number]': String(page), 'page[size]': String(perPage) });
  if (search) params.set('filter[search]', search);
  if (routerId) params.set('filter[router_id]', String(routerId));
  const res = await apiClient.get<{ data: Array<{ attributes: HotspotProfile }>; meta: { total: number; last_page: number; current_page: number } }>(`/hotspot/profiles?${params.toString()}`);
  return res.data;
};

export const getHotspotProfile = async (id: number): Promise<HotspotProfile> =>
  resource((await apiClient.get<Resource<HotspotProfile>>(`/hotspot/profiles/${id}`)).data);

export const createHotspotProfile = async (data: Partial<HotspotProfileFormValues>): Promise<HotspotProfile> =>
  resource((await apiClient.post<Resource<HotspotProfile>>('/hotspot/profiles', data)).data);

export const updateHotspotProfile = async (id: number, data: Partial<HotspotProfileFormValues>): Promise<HotspotProfile> =>
  resource((await apiClient.put<Resource<HotspotProfile>>(`/hotspot/profiles/${id}`, data)).data);

export const deleteHotspotProfile = async (id: number): Promise<void> => {
  await apiClient.delete(`/hotspot/profiles/${id}`);
};

// ─── Vouchers ─────────────────────────────────────────────────────────────────

export const getVouchers = async (page = 1, perPage = 15, status?: string, batchId?: number, search?: string): Promise<{ data: Array<{ attributes: Voucher }>; meta: { total: number; last_page: number; current_page: number } }> => {
  const params = new URLSearchParams({ 'page[number]': String(page), 'page[size]': String(perPage) });
  if (status) params.set('filter[status]', status);
  if (batchId) params.set('filter[batch_id]', String(batchId));
  if (search) params.set('filter[search]', search);
  const res = await apiClient.get<{ data: Array<{ attributes: Voucher }>; meta: { total: number; last_page: number; current_page: number } }>(`/hotspot/vouchers?${params.toString()}`);
  return res.data;
};

export const getVoucherBatches = async (page = 1, perPage = 15): Promise<{ data: Array<{ attributes: VoucherBatch }>; meta: { total: number; last_page: number; current_page: number } }> => {
  const params = new URLSearchParams({ 'page[number]': String(page), 'page[size]': String(perPage) });
  const res = await apiClient.get<{ data: Array<{ attributes: VoucherBatch }>; meta: { total: number; last_page: number; current_page: number } }>(`/hotspot/vouchers/batches?${params.toString()}`);
  return res.data;
};

export const generateVoucherBatch = async (data: Partial<GenerateVoucherBatchValues>): Promise<VoucherBatch> =>
  resource((await apiClient.post<Resource<VoucherBatch>>('/hotspot/vouchers/generate', data)).data);

export const revokeVoucher = async (id: number): Promise<Voucher> =>
  resource((await apiClient.post<Resource<Voucher>>(`/hotspot/vouchers/${id}/revoke`)).data);

export const getVoucherStats = async (): Promise<VoucherStats> =>
  resource((await apiClient.get<Resource<VoucherStats>>('/hotspot/vouchers/stats')).data);

export const printVoucherBatch = async (batchId: number): Promise<{ batch: VoucherBatch; vouchers: Array<{ code: string; status: string; time_limit: string | null; data_limit_mb: number | null; price: number | null; expires_at: string | null; qr_placeholder: string }> }> =>
  resource((await apiClient.get<Resource<{ batch: VoucherBatch; vouchers: Array<{ code: string; status: string; time_limit: string | null; data_limit_mb: number | null; price: number | null; expires_at: string | null; qr_placeholder: string }> }>>(`/hotspot/vouchers/batch/${batchId}/print`)).data);

// ─── Active Sessions ──────────────────────────────────────────────────────────

export const getActiveSessions = async (routerId?: number): Promise<HotspotActiveSession[]> => {
  const params = new URLSearchParams();
  if (routerId) params.set('router_id', String(routerId));
  const res = await apiClient.get<{ data: Array<{ attributes: HotspotActiveSession }> }>(`/hotspot/sessions/active?${params.toString()}`);
  return res.data.data.map((item) => item.attributes);
};

export const disconnectActiveSession = async (routerId: number, sessionId: string): Promise<void> => {
  await apiClient.post('/hotspot/sessions/active/disconnect', { router_id: routerId, session_id: sessionId });
};

export const forceLogoutUser = async (username: string): Promise<void> => {
  await apiClient.post('/hotspot/sessions/force-logout', { username });
};

export const getSessionHistory = async (page = 1, perPage = 15, userId?: number, routerId?: number, username?: string): Promise<{ items: HotspotSessionHistory[]; total: number; lastPage: number }> => {
  const params = new URLSearchParams({ 'page[number]': String(page), 'page[size]': String(perPage) });
  if (userId) params.set('filter[user_id]', String(userId));
  if (routerId) params.set('filter[router_id]', String(routerId));
  if (username) params.set('filter[username]', username);

  const res = await apiClient.get<{ data: Array<{ attributes: HotspotSessionHistory }>; meta: { total: number; last_page: number; current_page: number } }>(`/hotspot/sessions/history?${params.toString()}`);
  return {
    items: res.data.data.map((item) => item.attributes),
    total: res.data.meta.total,
    lastPage: res.data.meta.last_page,
  };
};

export const getUserStatistics = async (userId: number): Promise<HotspotUserStatistics> =>
  resource((await apiClient.get<Resource<HotspotUserStatistics>>(`/hotspot/users/${userId}/statistics`)).data);

// ─── Synchronization ──────────────────────────────────────────────────────────

export const syncHotspotRouter = async (routerId: number): Promise<HotspotSyncResult> =>
  resource((await apiClient.post<Resource<HotspotSyncResult>>(`/hotspot/sync/router/${routerId}`)).data);

export const syncHotspotUser = async (userId: number): Promise<HotspotSyncResult> =>
  resource((await apiClient.post<Resource<HotspotSyncResult>>(`/hotspot/sync/user/${userId}`)).data);

export const detectMissingHotspot = async (routerId?: number): Promise<Array<Record<string, unknown>>> => {
  const params = routerId ? `?router_id=${routerId}` : '';
  const res = await apiClient.post<{ data: Array<{ attributes: Record<string, unknown> }> }>(`/hotspot/sync/detect-missing${params}`);
  return res.data.data.map((item) => item.attributes);
};

export const repairHotspotSync = async (options: Record<string, unknown>): Promise<{ repaired_count: number; failed_count: number; logs: string[] }> =>
  resource((await apiClient.post<Resource<{ repaired_count: number; failed_count: number; logs: string[] }>>('/hotspot/sync/repair', options)).data);

export const importHotspotUsers = async (data: Partial<ImportHotspotUsersValues>): Promise<{ imported_count: number; failed_count: number; errors: string[] }> =>
  resource((await apiClient.post<Resource<{ imported_count: number; failed_count: number; errors: string[] }>>('/hotspot/sync/import', data)).data);

export const importHotspotProfiles = async (routerId: number): Promise<Array<Record<string, unknown>>> => {
  const res = await apiClient.post<{ data: Array<{ attributes: Record<string, unknown> }> }>('/hotspot/sync/import-profiles', { router_id: routerId });
  return res.data.data.map((item) => item.attributes);
};

export const getHotspotRouterProfiles = async (routerId: number): Promise<MikrotikHotspotProfile[]> => {
  if (!routerId || routerId <= 0) return [];
  const res = await apiClient.get<{ data: Array<{ attributes: MikrotikHotspotProfile }> }>(`/hotspot/routers/${routerId}/profiles`);
  return res.data.data.map((item) => item.attributes);
};

export const getHotspotSyncLogs = async (limit = 50, routerId?: number, userId?: number): Promise<HotspotSyncLog[]> => {
  const params = new URLSearchParams({ limit: String(limit) });
  if (routerId) params.set('router_id', String(routerId));
  if (userId) params.set('user_id', String(userId));
  const res = await apiClient.get<{ data: Array<{ attributes: HotspotSyncLog }> }>(`/hotspot/sync/logs?${params.toString()}`);
  return res.data.data.map((item) => item.attributes);
};

// ─── React Query Hooks ────────────────────────────────────────────────────────

export const useHotspotUsers = (filters: HotspotUserListFilters = {}) =>
  useQuery({
    queryKey: ['hotspot', 'users', filters],
    queryFn: () => getHotspotUsers(filters),
  });

export const useHotspotUser = (id: number) =>
  useQuery({
    queryKey: ['hotspot', 'user', id],
    queryFn: () => getHotspotUser(id),
    enabled: id > 0,
  });

export const useHotspotProfiles = (page = 1, perPage = 15, search?: string, routerId?: number) =>
  useQuery({
    queryKey: ['hotspot', 'profiles', page, perPage, search, routerId],
    queryFn: () => getHotspotProfiles(page, perPage, search, routerId),
  });

export const useHotspotProfile = (id: number) =>
  useQuery({
    queryKey: ['hotspot', 'profile', id],
    queryFn: () => getHotspotProfile(id),
    enabled: id > 0,
  });

export const useVouchers = (page = 1, perPage = 15, status?: string, batchId?: number, search?: string) =>
  useQuery({
    queryKey: ['hotspot', 'vouchers', page, perPage, status, batchId, search],
    queryFn: () => getVouchers(page, perPage, status, batchId, search),
  });

export const useVoucherBatches = (page = 1, perPage = 15) =>
  useQuery({
    queryKey: ['hotspot', 'voucher-batches', page, perPage],
    queryFn: () => getVoucherBatches(page, perPage),
  });

export const useVoucherStats = () =>
  useQuery({
    queryKey: ['hotspot', 'voucher-stats'],
    queryFn: getVoucherStats,
  });

export const useHotspotActiveSessions = (routerId?: number) =>
  useQuery({
    queryKey: ['hotspot', 'active-sessions', routerId],
    queryFn: () => getActiveSessions(routerId),
    refetchInterval: 15000,
  });

export const useHotspotSessionHistory = (page = 1, perPage = 15, userId?: number, routerId?: number, username?: string) =>
  useQuery({
    queryKey: ['hotspot', 'session-history', page, perPage, userId, routerId, username],
    queryFn: () => getSessionHistory(page, perPage, userId, routerId, username),
  });

export const useHotspotUserStatistics = (userId: number) =>
  useQuery({
    queryKey: ['hotspot', 'statistics', userId],
    queryFn: () => getUserStatistics(userId),
    enabled: userId > 0,
  });

export const useHotspotRouterProfiles = (routerId: number) =>
  useQuery({
    queryKey: ['hotspot', 'router-profiles', routerId],
    queryFn: () => getHotspotRouterProfiles(routerId),
    enabled: routerId > 0,
  });

export const useHotspotSyncLogs = (limit = 50, routerId?: number, userId?: number) =>
  useQuery({
    queryKey: ['hotspot', 'sync-logs', limit, routerId, userId],
    queryFn: () => getHotspotSyncLogs(limit, routerId, userId),
  });

// ─── Mutations ────────────────────────────────────────────────────────────────

export const useCreateHotspotUser = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createHotspotUser,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot'] }),
  });
};

export const useUpdateHotspotUser = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<EditHotspotUserFormValues> }) => updateHotspotUser(id, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['hotspot'] });
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'user', variables.id] });
    },
  });
};

export const useDeleteHotspotUser = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: deleteHotspotUser,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot'] }),
  });
};

export const useCreateHotspotProfile = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createHotspotProfile,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot', 'profiles'] }),
  });
};

export const useUpdateHotspotProfile = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<HotspotProfileFormValues> }) => updateHotspotProfile(id, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot', 'profiles'] }),
  });
};

export const useDeleteHotspotProfile = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: deleteHotspotProfile,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot', 'profiles'] }),
  });
};

export const useGenerateVoucherBatch = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: generateVoucherBatch,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'vouchers'] });
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'voucher-batches'] });
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'voucher-stats'] });
    },
  });
};
