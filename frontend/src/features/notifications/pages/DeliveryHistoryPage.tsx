import { useState } from 'react';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useDeliveries, useNotificationCatalog } from '../api/useNotifications';
import { DeliveryTable } from '../components/DeliveryTable';
import { NotificationSkeleton } from '../components/NotificationSkeleton';

export const DeliveryHistoryPage = () => {
  const [filters, setFilters] = useState({ channel: '', status: '', search: '', page: 1, per_page: 25 });
  const catalog = useNotificationCatalog();
  const list = useDeliveries(filters);

  if (list.isLoading && !list.data) {
    return <NotificationSkeleton />;
  }

  if (list.error) {
    return <Alert title="Delivery history unavailable">{apiErrorMessage(list.error)}</Alert>;
  }

  const deliveries = list.data?.data.map((row) => row.attributes) ?? [];
  const meta = list.data?.meta;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Communications</p>
        <h1 className="mt-2 text-2xl font-bold">Delivery History</h1>
        <p className="mt-1 text-sm text-slate-500">
          Audit trail of in-app, email, SMS, push, and webhook delivery attempts.
        </p>
      </header>

      <section className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-3 dark:border-slate-700 dark:bg-slate-900">
        <input
          className="h-10 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          placeholder="Search subject/body…"
          value={filters.search}
          onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value, page: 1 }))}
        />
        <select
          className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          value={filters.channel}
          onChange={(e) => setFilters((f) => ({ ...f, channel: e.target.value, page: 1 }))}
        >
          <option value="">All channels</option>
          {(catalog.data?.channels ?? []).map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
        <select
          className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          value={filters.status}
          onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value, page: 1 }))}
        >
          <option value="">All statuses</option>
          {['pending', 'queued', 'sent', 'failed', 'skipped'].map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
      </section>

      <DeliveryTable deliveries={deliveries} isLoading={list.isLoading} />

      {meta ? (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
          <span className="text-slate-500">{meta.total} deliveries</span>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={filters.page <= 1}
              onClick={() => setFilters((f) => ({ ...f, page: f.page - 1 }))}
            >
              Previous
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={filters.page >= meta.last_page}
              onClick={() => setFilters((f) => ({ ...f, page: f.page + 1 }))}
            >
              Next
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
