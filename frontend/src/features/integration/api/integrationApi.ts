import { apiClient } from '@/lib/apiClient';

import type {
  ApiCollection,
  ApiResource,
  ApiRequestLogItem,
  ApiKeyItem,
  ClientApplicationItem,
  ConnectorItem,
  EventRegistryItem,
  IntegrationDashboardData,
  IntegrationFilters,
  WebhookDeliveryItem,
  WebhookItem,
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

export const integrationApi = {
  // Dashboard
  dashboard: async () =>
    attrs((await apiClient.get<ApiResource<IntegrationDashboardData>>('/integration/dashboard')).data),

  // API Keys
  listApiKeys: async (filters: IntegrationFilters = {}) =>
    (await apiClient.get<ApiCollection<ApiKeyItem>>(`/integration/api-keys${qs(filters as Record<string, unknown>)}`)).data,
  getApiKey: async (id: number) =>
    attrs((await apiClient.get<ApiResource<ApiKeyItem>>(`/integration/api-keys/${id}`)).data),
  createApiKey: async (data: Partial<ApiKeyItem>) => {
    const res = await apiClient.post<ApiResource<ApiKeyItem> & { meta?: { plain_text_key?: string } }>(
      '/integration/api-keys', data,
    );
    return { attributes: res.data.data.attributes, meta: res.data.meta ?? res.data.data.meta ?? {} };
  },
  updateApiKey: async (id: number, data: Partial<ApiKeyItem>) =>
    attrs((await apiClient.put<ApiResource<ApiKeyItem>>(`/integration/api-keys/${id}`, data)).data),
  deleteApiKey: async (id: number) => { await apiClient.delete(`/integration/api-keys/${id}`); },
  regenerateApiKey: async (id: number) => {
    const res = await apiClient.post<ApiResource<ApiKeyItem> & { meta?: { plain_text_key?: string } }>(
      `/integration/api-keys/${id}/regenerate`,
    );
    return { attributes: res.data.data.attributes, meta: res.data.meta ?? res.data.data.meta ?? {} };
  },

  // Client Applications
  listApplications: async (page = 1, perPage = 25) =>
    (await apiClient.get<ApiCollection<ClientApplicationItem>>(`/integration/applications?page=${page}&per_page=${perPage}`)).data,
  getApplication: async (id: number) =>
    attrs((await apiClient.get<ApiResource<ClientApplicationItem>>(`/integration/applications/${id}`)).data),
  createApplication: async (data: Partial<ClientApplicationItem>) =>
    attrs((await apiClient.post<ApiResource<ClientApplicationItem>>('/integration/applications', data)).data),
  updateApplication: async (id: number, data: Partial<ClientApplicationItem>) =>
    attrs((await apiClient.put<ApiResource<ClientApplicationItem>>(`/integration/applications/${id}`, data)).data),
  deleteApplication: async (id: number) => { await apiClient.delete(`/integration/applications/${id}`); },

  // Webhooks
  listWebhooks: async (filters: IntegrationFilters = {}) =>
    (await apiClient.get<ApiCollection<WebhookItem>>(`/integration/webhooks${qs(filters as Record<string, unknown>)}`)).data,
  getWebhook: async (id: number) =>
    attrs((await apiClient.get<ApiResource<WebhookItem>>(`/integration/webhooks/${id}`)).data),
  createWebhook: async (data: Partial<WebhookItem>) =>
    attrs((await apiClient.post<ApiResource<WebhookItem>>('/integration/webhooks', data)).data),
  updateWebhook: async (id: number, data: Partial<WebhookItem>) =>
    attrs((await apiClient.put<ApiResource<WebhookItem>>(`/integration/webhooks/${id}`, data)).data),
  deleteWebhook: async (id: number) => { await apiClient.delete(`/integration/webhooks/${id}`); },
  rotateWebhookSecret: async (id: number) => {
    const res = await apiClient.post<ApiResource<WebhookItem> & { meta?: { new_secret?: string } }>(
      `/integration/webhooks/${id}/rotate-secret`,
    );
    return { attributes: res.data.data.attributes, meta: res.data.meta ?? res.data.data.meta ?? {} };
  },
  testWebhook: async (id: number, payload?: Record<string, unknown>) =>
    attrs((await apiClient.post<ApiResource<Record<string, unknown>>>(`/integration/webhooks/${id}/test`, { payload })).data),

  // Deliveries
  listDeliveries: async (filters: IntegrationFilters = {}) =>
    (await apiClient.get<ApiCollection<WebhookDeliveryItem>>(`/integration/deliveries${qs(filters as Record<string, unknown>)}`)).data,
  getDelivery: async (id: number) =>
    attrs((await apiClient.get<ApiResource<WebhookDeliveryItem>>(`/integration/deliveries/${id}`)).data),
  retryDelivery: async (id: number) =>
    attrs((await apiClient.post<ApiResource<{ success: boolean }>>(`/integration/deliveries/${id}/retry`)).data),

  // Events
  listEvents: async (page = 1, perPage = 50, sourceModule?: string) => {
    const params: Record<string, unknown> = { page, per_page: perPage };
    if (sourceModule) params.source_module = sourceModule;
    return (await apiClient.get<ApiCollection<EventRegistryItem>>(`/integration/events${qs(params)}`)).data;
  },
  getEvent: async (id: number) =>
    attrs((await apiClient.get<ApiResource<EventRegistryItem>>(`/integration/events/${id}`)).data),

  // Connectors
  listConnectors: async () =>
    (await apiClient.get<ApiCollection<ConnectorItem>>('/integration/connectors')).data,
  getConnector: async (type: string) =>
    attrs((await apiClient.get<ApiResource<ConnectorItem>>(`/integration/connectors/${type}`)).data),
  updateConnector: async (type: string, data: Partial<ConnectorItem>) =>
    attrs((await apiClient.put<ApiResource<ConnectorItem>>(`/integration/connectors/${type}`, data)).data),
  testConnector: async (type: string) =>
    attrs((await apiClient.post<ApiResource<{ success: boolean; message: string }>>(`/integration/connectors/${type}/test`)).data),

  // Request Logs
  listRequestLogs: async (filters: IntegrationFilters = {}) =>
    (await apiClient.get<ApiCollection<ApiRequestLogItem>>(`/integration/request-logs${qs(filters as Record<string, unknown>)}`)).data,
};
