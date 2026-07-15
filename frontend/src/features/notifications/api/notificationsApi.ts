import { apiClient } from '@/lib/apiClient';

import type {
  ApiCollection,
  ApiResource,
  DeliveryRecord,
  NotificationCatalog,
  NotificationEventRecord,
  NotificationFilters,
  NotificationItem,
  NotificationTemplate,
  UserPreferencesPayload,
  UserPreferenceRow,
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

export const notificationsApi = {
  list: async (filters: NotificationFilters = {}) =>
    (await apiClient.get<ApiCollection<NotificationItem>>(`/notifications${qs(filters as Record<string, unknown>)}`)).data,

  unreadCount: async () => {
    const res = await apiClient.get<ApiResource<{ unread_count: number }>>('/notifications/unread-count');
    return attrs(res.data).unread_count;
  },

  get: async (id: number) =>
    attrs((await apiClient.get<ApiResource<NotificationItem>>(`/notifications/${id}`)).data),

  markRead: async (id: number) =>
    attrs((await apiClient.patch<ApiResource<NotificationItem>>(`/notifications/${id}/read`)).data),

  markAllRead: async () =>
    attrs((await apiClient.patch<ApiResource<{ marked_read: number }>>('/notifications/read-all')).data),

  archive: async (id: number) =>
    attrs((await apiClient.patch<ApiResource<NotificationItem>>(`/notifications/${id}/archive`)).data),

  remove: async (id: number) => {
    await apiClient.delete(`/notifications/${id}`);
  },

  catalog: async () =>
    attrs((await apiClient.get<ApiResource<NotificationCatalog>>('/notifications/catalog')).data),

  preferences: async () =>
    attrs((await apiClient.get<ApiResource<UserPreferencesPayload>>('/notifications/preferences')).data),

  updatePreferences: async (preferences: UserPreferenceRow[]) =>
    attrs(
      (
        await apiClient.put<ApiResource<UserPreferencesPayload>>('/notifications/preferences', {
          preferences,
        })
      ).data,
    ),

  templates: async (filters: Record<string, unknown> = {}) =>
    (await apiClient.get<ApiCollection<NotificationTemplate>>(`/notifications/templates${qs(filters)}`)).data,

  template: async (id: number) =>
    attrs((await apiClient.get<ApiResource<NotificationTemplate>>(`/notifications/templates/${id}`)).data),

  createTemplate: async (data: Partial<NotificationTemplate>) =>
    attrs((await apiClient.post<ApiResource<NotificationTemplate>>('/notifications/templates', data)).data),

  updateTemplate: async (id: number, data: Partial<NotificationTemplate>) =>
    attrs((await apiClient.put<ApiResource<NotificationTemplate>>(`/notifications/templates/${id}`, data)).data),

  deleteTemplate: async (id: number) => {
    await apiClient.delete(`/notifications/templates/${id}`);
  },

  previewTemplate: async (id: number, sample: Record<string, unknown> = {}) =>
    attrs(
      (
        await apiClient.post<ApiResource<{ subject: string; body: string }>>(
          `/notifications/templates/${id}/preview`,
          { sample },
        )
      ).data,
    ),

  deliveries: async (filters: Record<string, unknown> = {}) =>
    (await apiClient.get<ApiCollection<DeliveryRecord>>(`/notifications/deliveries${qs(filters)}`)).data,

  delivery: async (id: number) =>
    attrs((await apiClient.get<ApiResource<DeliveryRecord>>(`/notifications/deliveries/${id}`)).data),

  events: async (filters: Record<string, unknown> = {}) =>
    (await apiClient.get<ApiCollection<NotificationEventRecord>>(`/notifications/events${qs(filters)}`)).data,
};
