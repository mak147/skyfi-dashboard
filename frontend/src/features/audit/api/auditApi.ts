import { apiClient } from '@/lib/apiClient';

import type {
  ActivityEvent,
  ActivityFilters,
  ApiCollection,
  ApiResource,
  AuditDashboardStats,
  AuditExport,
  AuditFilterOptions,
  AuditLog,
  AuditLogFilters,
  CompliancePolicy,
  ExportRequest,
  RetentionPolicy,
} from '../types';

const qs = (params: Record<string, unknown> = {}) => {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      search.set(key, String(value));
    }
  });
  const s = search.toString();
  return s ? `?${s}` : '';
};

const attrs = <T>(response: ApiResource<T>) => response.data.attributes;

export const auditApi = {
  // Audit Logs
  listLogs: async (filters: AuditLogFilters = {}) =>
    (await apiClient.get<ApiCollection<AuditLog>>(`/audit/logs${qs(filters as Record<string, unknown>)}`)).data,

  getLog: async (id: number) =>
    attrs((await apiClient.get<ApiResource<AuditLog>>(`/audit/logs/${id}`)).data),

  getDashboard: async () =>
    attrs((await apiClient.get<ApiResource<AuditDashboardStats>>('/audit/dashboard')).data),

  getFilterOptions: async () =>
    attrs((await apiClient.get<ApiResource<AuditFilterOptions>>('/audit/filter-options')).data),

  getResourceHistory: async (entityType: string, entityId: number, page = 1, perPage = 25) =>
    (await apiClient.get<ApiCollection<AuditLog>>(`/audit/resource-history${qs({ entity_type: entityType, entity_id: entityId, page, per_page: perPage })}`)).data,

  // Activity
  listActivity: async (filters: ActivityFilters = {}) =>
    (await apiClient.get<ApiCollection<ActivityEvent>>(`/audit/activity${qs(filters as Record<string, unknown>)}`)).data,

  getUserActivity: async (userId: number, filters: Omit<ActivityFilters, 'user_id'> = {}) =>
    (await apiClient.get<ApiCollection<ActivityEvent>>(`/audit/users/${userId}/activity${qs(filters as Record<string, unknown>)}`)).data,

  // Exports
  requestExport: async (data: ExportRequest = {}) =>
    attrs((await apiClient.post<ApiResource<AuditExport>>('/audit/export', data)).data),

  listExports: async () =>
    (await apiClient.get<ApiCollection<AuditExport>>('/audit/exports')).data,

  getExportDownload: async (id: number) =>
    attrs((await apiClient.get<ApiResource<AuditExport>>(`/audit/exports/${id}/download`)).data),

  // Compliance Policies
  listPolicies: async () =>
    (await apiClient.get<ApiCollection<CompliancePolicy>>('/compliance/policies')).data,

  getPolicy: async (id: number) =>
    attrs((await apiClient.get<ApiResource<CompliancePolicy>>(`/compliance/policies/${id}`)).data),

  createPolicy: async (data: Partial<CompliancePolicy>) =>
    attrs((await apiClient.post<ApiResource<CompliancePolicy>>('/compliance/policies', data)).data),

  updatePolicy: async (id: number, data: Partial<CompliancePolicy>) =>
    attrs((await apiClient.put<ApiResource<CompliancePolicy>>(`/compliance/policies/${id}`, data)).data),

  deletePolicy: async (id: number) => {
    await apiClient.delete(`/compliance/policies/${id}`);
  },

  // Retention Policies
  listRetentionPolicies: async () =>
    (await apiClient.get<ApiCollection<RetentionPolicy>>('/compliance/retention')).data,

  getRetentionPolicy: async (id: number) =>
    attrs((await apiClient.get<ApiResource<RetentionPolicy>>(`/compliance/retention/${id}`)).data),

  createRetentionPolicy: async (data: Partial<RetentionPolicy>) =>
    attrs((await apiClient.post<ApiResource<RetentionPolicy>>('/compliance/retention', data)).data),

  updateRetentionPolicy: async (id: number, data: Partial<RetentionPolicy>) =>
    attrs((await apiClient.put<ApiResource<RetentionPolicy>>(`/compliance/retention/${id}`, data)).data),

  deleteRetentionPolicy: async (id: number) => {
    await apiClient.delete(`/compliance/retention/${id}`);
  },
};
