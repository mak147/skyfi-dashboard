import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { notificationsApi } from './notificationsApi';
import type { NotificationFilters, NotificationTemplate, UserPreferenceRow } from '../types';

export const useNotifications = (filters: NotificationFilters = {}) =>
  useQuery({
    queryKey: ['notifications', 'list', filters],
    queryFn: () => notificationsApi.list(filters),
  });

export const useUnreadCount = () =>
  useQuery({
    queryKey: ['notifications', 'unread-count'],
    queryFn: () => notificationsApi.unreadCount(),
    refetchInterval: 30_000,
  });

export const useMarkRead = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => notificationsApi.markRead(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
};

export const useMarkAllRead = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => notificationsApi.markAllRead(),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
};

export const useArchiveNotification = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => notificationsApi.archive(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
};

export const useDeleteNotification = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => notificationsApi.remove(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
};

export const useNotificationPreferences = () =>
  useQuery({
    queryKey: ['notifications', 'preferences'],
    queryFn: () => notificationsApi.preferences(),
  });

export const useUpdatePreferences = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (preferences: UserPreferenceRow[]) => notificationsApi.updatePreferences(preferences),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications', 'preferences'] });
    },
  });
};

export const useNotificationTemplates = (filters: Record<string, unknown> = {}) =>
  useQuery({
    queryKey: ['notifications', 'templates', filters],
    queryFn: () => notificationsApi.templates(filters),
  });

export const useSaveTemplate = (id?: number) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<NotificationTemplate>) =>
      id ? notificationsApi.updateTemplate(id, data) : notificationsApi.createTemplate(data),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications', 'templates'] });
    },
  });
};

export const useDeleteTemplate = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => notificationsApi.deleteTemplate(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['notifications', 'templates'] });
    },
  });
};

export const useDeliveries = (filters: Record<string, unknown> = {}) =>
  useQuery({
    queryKey: ['notifications', 'deliveries', filters],
    queryFn: () => notificationsApi.deliveries(filters),
  });

export const useNotificationCatalog = () =>
  useQuery({
    queryKey: ['notifications', 'catalog'],
    queryFn: () => notificationsApi.catalog(),
    staleTime: 300_000,
  });
