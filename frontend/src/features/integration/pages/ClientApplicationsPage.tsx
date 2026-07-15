import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useApplications, useCreateApplication, useDeleteApplication } from '../api/useIntegration';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';
import type { ClientApplicationItem } from '../types';

export const ClientApplicationsPage = () => {
  const [page, setPage] = useState(1);
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState('');
  const [newRateLimit, setNewRateLimit] = useState(60);
  const { data, isLoading, error } = useApplications(page);
  const createApp = useCreateApplication();
  const deleteApp = useDeleteApplication();

  if (isLoading && !data) {
    return <IntegrationSkeleton />;
  }

  if (error) {
    return <Alert title="Applications unavailable">{apiErrorMessage(error)}</Alert>;
  }

  const items = data?.data.map((row) => row.attributes) ?? [];
  const meta = data?.meta;

  const handleCreate = () => {
    if (!newName) return;
    createApp.mutate({ name: newName, rate_limit_per_minute: newRateLimit } as Partial<ClientApplicationItem>, {
      onSuccess: () => { setNewName(''); setShowCreate(false); },
    });
  };

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex items-end justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Client Applications</h1>
          <p className="mt-1 text-sm text-slate-500">Register and manage external applications that consume the API.</p>
        </div>
        <Button size="sm" onClick={() => setShowCreate(!showCreate)}>{showCreate ? 'Cancel' : 'Register Application'}</Button>
      </header>

      {showCreate && (
        <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 space-y-3">
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Name</label>
            <input className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newName} onChange={(e) => setNewName(e.target.value)} placeholder="e.g. Mobile App" />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Rate Limit (req/min)</label>
            <input type="number" className="mt-1 w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newRateLimit} onChange={(e) => setNewRateLimit(Number(e.target.value))} min={1} />
          </div>
          <Button size="sm" disabled={createApp.isPending} onClick={handleCreate}>{createApp.isPending ? 'Creating...' : 'Create'}</Button>
        </div>
      )}

      <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 dark:bg-slate-800">
            <tr>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Name</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Rate Limit</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Status</th>
              <th className="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
            {items.map((app) => (
              <tr key={app.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">{app.name}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{app.rate_limit_per_minute}/min</td>
                <td className="px-4 py-3">
                  <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${app.is_active ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-50 text-red-700'}`}>
                    {app.is_active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="px-4 py-3 text-right">
                  <Button size="sm" variant="secondary" onClick={() => { if (confirm('Delete this application?')) deleteApp.mutate(app.id); }}>Delete</Button>
                </td>
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-400">No applications registered.</td></tr>}
          </tbody>
        </table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-slate-500">{meta.total} applications</span>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => setPage(meta.current_page - 1)}>Prev</Button>
            <Button size="sm" variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => setPage(meta.current_page + 1)}>Next</Button>
          </div>
        </div>
      )}
    </div>
  );
};
