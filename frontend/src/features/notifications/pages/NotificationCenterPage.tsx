import { useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import {
  useArchiveNotification,
  useMarkAllRead,
  useMarkRead,
  useNotificationCatalog,
  useNotifications,
} from '../api/useNotifications';
import { NotificationFiltersBar } from '../components/NotificationFilters';
import { NotificationList } from '../components/NotificationList';
import { NotificationSkeleton } from '../components/NotificationSkeleton';
import type { NotificationFilters } from '../types';

export const NotificationCenterPage = () => {
  const { can } = usePermissions();
  const [filters, setFilters] = useState<NotificationFilters>({ page: 1, per_page: 20 });
  const catalog = useNotificationCatalog();
  const list = useNotifications(filters);
  const markRead = useMarkRead();
  const markAll = useMarkAllRead();
  const archive = useArchiveNotification();

  if (list.isLoading && !list.data) {
    return <NotificationSkeleton />;
  }

  if (list.error) {
    return <Alert title="Notification center unavailable">{apiErrorMessage(list.error)}</Alert>;
  }

  const items = list.data?.data.map((row) => row.attributes) ?? [];
  const meta = list.data?.meta;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Communications</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Notification Center</h1>
          <p className="mt-1 text-sm text-slate-500">
            Centralized in-app inbox for billing, support, network, field, and inventory events.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button variant="secondary" disabled={markAll.isPending} onClick={() => markAll.mutate()}>
            Mark all read
          </Button>
          {can('notifications.preferences') ? (
            <Link to="/notifications/preferences">
              <Button variant="secondary">Preferences</Button>
            </Link>
          ) : null}
          {can('notifications.templates') ? (
            <Link to="/notifications/templates">
              <Button variant="secondary">Templates</Button>
            </Link>
          ) : null}
          {can('notifications.manage') ? (
            <Link to="/notifications/deliveries">
              <Button variant="secondary">Delivery history</Button>
            </Link>
          ) : null}
        </div>
      </header>

      <NotificationFiltersBar
        filters={filters}
        categories={catalog.data?.categories ?? []}
        onChange={setFilters}
      />

      <NotificationList
        items={items}
        isLoading={list.isLoading}
        onRead={(id) => markRead.mutate(id)}
        onArchive={(id) => archive.mutate(id)}
      />

      {meta ? (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
          <span className="text-slate-500">{meta.total} notifications</span>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={(filters.page ?? 1) <= 1}
              onClick={() => setFilters((f) => ({ ...f, page: Math.max(1, (f.page ?? 1) - 1) }))}
            >
              Previous
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={(filters.page ?? 1) >= meta.last_page}
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) + 1 }))}
            >
              Next
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
