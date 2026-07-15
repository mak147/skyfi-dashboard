import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useWebhooks, useCreateWebhook, useDeleteWebhook } from '../api/useIntegration';
import { WebhookTable } from '../components/WebhookTable';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';
import type { WebhookItem, IntegrationFilters } from '../types';

export const WebhooksPage = () => {
  const [filters, setFilters] = useState<IntegrationFilters>({ page: 1, per_page: 20 });
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState('');
  const [newUrl, setNewUrl] = useState('');
  const [newEvents, setNewEvents] = useState('');
  const [newInbound, setNewInbound] = useState(false);
  const { data, isLoading, error } = useWebhooks(filters);
  const createWebhook = useCreateWebhook();
  const deleteWebhook = useDeleteWebhook();

  if (isLoading && !data) {
    return <IntegrationSkeleton />;
  }

  if (error) {
    return <Alert title="Webhooks unavailable">{apiErrorMessage(error)}</Alert>;
  }

  const items = data?.data.map((row) => row.attributes) ?? [];
  const meta = data?.meta;

  const handleCreate = () => {
    if (!newName || !newUrl || !newEvents) return;
    createWebhook.mutate({
      name: newName,
      url: newUrl,
      events: newEvents.split(',').map((e) => e.trim()),
      is_inbound: newInbound,
    } as Partial<WebhookItem>, {
      onSuccess: () => { setNewName(''); setNewUrl(''); setNewEvents(''); setShowCreate(false); },
    });
  };

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex items-end justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Webhooks</h1>
          <p className="mt-1 text-sm text-slate-500">Configure outbound and inbound webhooks for real-time event delivery.</p>
        </div>
        <Button size="sm" onClick={() => setShowCreate(!showCreate)}>{showCreate ? 'Cancel' : 'Add Webhook'}</Button>
      </header>

      {showCreate && (
        <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 space-y-3">
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Name</label>
            <input className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newName} onChange={(e) => setNewName(e.target.value)} placeholder="e.g. Slack Notifications" />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">URL</label>
            <input className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newUrl} onChange={(e) => setNewUrl(e.target.value)} placeholder="https://hooks.example.com/..." />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Events (comma-separated)</label>
            <input className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newEvents} onChange={(e) => setNewEvents(e.target.value)} placeholder="invoice.generated,payment.completed" />
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={newInbound} onChange={(e) => setNewInbound(e.target.checked)} className="rounded" />
            Inbound webhook (receives external events)
          </label>
          <Button size="sm" disabled={createWebhook.isPending} onClick={handleCreate}>{createWebhook.isPending ? 'Creating...' : 'Create'}</Button>
        </div>
      )}

      <WebhookTable
        items={items}
        meta={meta}
        onPageChange={(p) => setFilters((f) => ({ ...f, page: p }))}
        onDelete={(id) => { if (confirm('Delete this webhook?')) deleteWebhook.mutate(id); }}
      />
    </div>
  );
};
