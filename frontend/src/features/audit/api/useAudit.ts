import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { auditApi } from './auditApi';

import type {
  ActivityFilters,
  AuditLogFilters,
  CompliancePolicy,
  ExportRequest,
  RetentionPolicy,
} from '../types';

// ─── Audit Logs ────────────────────────────────────────────────────────

export const useAuditLogs = (filters: AuditLogFilters = {}) =>
  useQuery({
    queryKey: ['audit', 'logs', filters],
    queryFn: () => auditApi.listLogs(filters),
  });

export const useAuditLog = (id: number) =>
  useQuery({
    queryKey: ['audit', 'logs', id],
    queryFn: () => auditApi.getLog(id),
    enabled: id > 0,
  });

export const useAuditDashboard = () =>
  useQuery({
    queryKey: ['audit', 'dashboard'],
    queryFn: auditApi.getDashboard,
    refetchInterval: 30000,
  });

export const useAuditFilterOptions = () =>
  useQuery({
    queryKey: ['audit', 'filter-options'],
    queryFn: auditApi.getFilterOptions,
    staleTime: 300000,
  });

export const useResourceHistory = (entityType: string, entityId: number, page = 1) =>
  useQuery({
    queryKey: ['audit', 'resource-history', entityType, entityId, page],
    queryFn: () => auditApi.getResourceHistory(entityType, entityId, page),
    enabled: entityType !== '' && entityId > 0,
  });

// ─── Activity ──────────────────────────────────────────────────────────

export const useActivity = (filters: ActivityFilters = {}) =>
  useQuery({
    queryKey: ['audit', 'activity', filters],
    queryFn: () => auditApi.listActivity(filters),
  });

export const useUserActivity = (userId: number, filters: Omit<ActivityFilters, 'user_id'> = {}) =>
  useQuery({
    queryKey: ['audit', 'user-activity', userId, filters],
    queryFn: () => auditApi.getUserActivity(userId, filters),
    enabled: userId > 0,
  });

// ─── Exports ───────────────────────────────────────────────────────────

export const useAuditExports = () =>
  useQuery({
    queryKey: ['audit', 'exports'],
    queryFn: auditApi.listExports,
  });

export const useRequestExport = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: ExportRequest) => auditApi.requestExport(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['audit', 'exports'] }),
  });
};

// ─── Compliance Policies ───────────────────────────────────────────────

export const useCompliancePolicies = () =>
  useQuery({
    queryKey: ['compliance', 'policies'],
    queryFn: auditApi.listPolicies,
  });

export const useCreatePolicy = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<CompliancePolicy>) => auditApi.createPolicy(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance', 'policies'] }),
  });
};

export const useUpdatePolicy = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CompliancePolicy> }) => auditApi.updatePolicy(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance', 'policies'] }),
  });
};

export const useDeletePolicy = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => auditApi.deletePolicy(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance', 'policies'] }),
  });
};

// ─── Retention Policies ────────────────────────────────────────────────

export const useRetentionPolicies = () =>
  useQuery({
    queryKey: ['compliance', 'retention'],
    queryFn: auditApi.listRetentionPolicies,
  });

export const useCreateRetentionPolicy = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<RetentionPolicy>) => auditApi.createRetentionPolicy(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance', 'retention'] }),
  });
};

export const useUpdateRetentionPolicy = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<RetentionPolicy> }) => auditApi.updateRetentionPolicy(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance', 'retention'] }),
  });
};

export const useDeleteRetentionPolicy = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => auditApi.deleteRetentionPolicy(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance', 'retention'] }),
  });
};
