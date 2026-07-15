import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { integrationApi } from './integrationApi';
import type { IntegrationFilters, WebhookItem, ApiKeyItem, ClientApplicationItem, ConnectorItem } from '../types';

// Dashboard
export const useIntegrationDashboard = () =>
  useQuery({
    queryKey: ['integration', 'dashboard'],
    queryFn: () => integrationApi.dashboard(),
    staleTime: 60_000,
  });

// API Keys
export const useApiKeys = (filters: IntegrationFilters = {}) =>
  useQuery({
    queryKey: ['integration', 'api-keys', filters],
    queryFn: () => integrationApi.listApiKeys(filters),
  });

export const useApiKey = (id: number) =>
  useQuery({
    queryKey: ['integration', 'api-keys', id],
    queryFn: () => integrationApi.getApiKey(id),
    enabled: id > 0,
  });

export const useCreateApiKey = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<ApiKeyItem>) => integrationApi.createApiKey(data),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'api-keys'] }); },
  });
};

export const useUpdateApiKey = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<ApiKeyItem> }) => integrationApi.updateApiKey(id, data),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'api-keys'] }); },
  });
};

export const useDeleteApiKey = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => integrationApi.deleteApiKey(id),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'api-keys'] }); },
  });
};

export const useRegenerateApiKey = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => integrationApi.regenerateApiKey(id),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'api-keys'] }); },
  });
};

// Client Applications
export const useApplications = (page = 1) =>
  useQuery({
    queryKey: ['integration', 'applications', page],
    queryFn: () => integrationApi.listApplications(page),
  });

export const useCreateApplication = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<ClientApplicationItem>) => integrationApi.createApplication(data),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'applications'] }); },
  });
};

export const useDeleteApplication = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => integrationApi.deleteApplication(id),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'applications'] }); },
  });
};

// Webhooks
export const useWebhooks = (filters: IntegrationFilters = {}) =>
  useQuery({
    queryKey: ['integration', 'webhooks', filters],
    queryFn: () => integrationApi.listWebhooks(filters),
  });

export const useWebhook = (id: number) =>
  useQuery({
    queryKey: ['integration', 'webhooks', id],
    queryFn: () => integrationApi.getWebhook(id),
    enabled: id > 0,
  });

export const useCreateWebhook = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<WebhookItem>) => integrationApi.createWebhook(data),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'webhooks'] }); },
  });
};

export const useUpdateWebhook = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<WebhookItem> }) => integrationApi.updateWebhook(id, data),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'webhooks'] }); },
  });
};

export const useDeleteWebhook = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => integrationApi.deleteWebhook(id),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'webhooks'] }); },
  });
};

export const useRotateWebhookSecret = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => integrationApi.rotateWebhookSecret(id),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'webhooks'] }); },
  });
};

export const useTestWebhook = () =>
  useMutation({
    mutationFn: ({ id, payload }: { id: number; payload?: Record<string, unknown> }) => integrationApi.testWebhook(id, payload),
  });

// Deliveries
export const useDeliveries = (filters: IntegrationFilters = {}) =>
  useQuery({
    queryKey: ['integration', 'deliveries', filters],
    queryFn: () => integrationApi.listDeliveries(filters),
  });

export const useRetryDelivery = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => integrationApi.retryDelivery(id),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'deliveries'] }); },
  });
};

// Events
export const useEvents = (page = 1, sourceModule?: string) =>
  useQuery({
    queryKey: ['integration', 'events', page, sourceModule],
    queryFn: () => integrationApi.listEvents(page, 50, sourceModule),
  });

// Connectors
export const useConnectors = () =>
  useQuery({
    queryKey: ['integration', 'connectors'],
    queryFn: () => integrationApi.listConnectors(),
    staleTime: 120_000,
  });

export const useUpdateConnector = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ type, data }: { type: string; data: Partial<ConnectorItem> }) => integrationApi.updateConnector(type, data),
    onSuccess: () => { void qc.invalidateQueries({ queryKey: ['integration', 'connectors'] }); },
  });
};

export const useTestConnector = () =>
  useMutation({
    mutationFn: (type: string) => integrationApi.testConnector(type),
  });

// Request Logs
export const useRequestLogs = (filters: IntegrationFilters = {}) =>
  useQuery({
    queryKey: ['integration', 'request-logs', filters],
    queryFn: () => integrationApi.listRequestLogs(filters),
  });
