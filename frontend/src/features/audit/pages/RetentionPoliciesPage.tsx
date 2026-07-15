import { useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useCreateRetentionPolicy, useDeleteRetentionPolicy, useRetentionPolicies } from '../api/useAudit';
import { AuditSkeleton } from '../components/AuditSkeleton';

export const RetentionPoliciesPage = () => {
  const { can } = usePermissions();
  const retention = useRetentionPolicies();
  const createRetention = useCreateRetentionPolicy();
  const deleteRetention = useDeleteRetentionPolicy();

  const [showForm, setShowForm] = useState(false);
  const [name, setName] = useState('');
  const [days, setDays] = useState(365);
  const [module, setModule] = useState('*');
  const [desc, setDesc] = useState('');
  const [autoArchive, setAutoArchive] = useState(false);

  if (retention.isLoading) {
    return <AuditSkeleton />;
  }

  if (retention.error) {
    return <Alert title="Retention policies unavailable">{apiErrorMessage(retention.error)}</Alert>;
  }

  const items = retention.data?.data.map((r) => r.attributes) ?? [];

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Audit & Compliance</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Retention Policies</h1>
          <p className="mt-1 text-sm text-slate-500">
            Configure how long audit data is retained and archived.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to="/audit/compliance">
            <Button variant="secondary">Compliance Center</Button>
          </Link>
          {can('compliance.manage') && (
            <Button size="sm" onClick={() => setShowForm(!showForm)}>
              {showForm ? 'Cancel' : 'Add Policy'}
            </Button>
          )}
        </div>
      </header>

      {showForm && (
        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
          <h3 className="text-sm font-bold text-slate-900 dark:text-white">New Retention Policy</h3>
          <div className="mt-3 grid gap-3 sm:grid-cols-2">
            <input
              type="text"
              placeholder="Policy name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            <input
              type="number"
              placeholder="Retention days"
              value={days}
              onChange={(e) => setDays(parseInt(e.target.value, 10) || 365)}
              className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            <input
              type="text"
              placeholder="Module (e.g. billing or *)"
              value={module}
              onChange={(e) => setModule(e.target.value)}
              className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            <input
              type="text"
              placeholder="Description"
              value={desc}
              onChange={(e) => setDesc(e.target.value)}
              className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
              <input
                type="checkbox"
                checked={autoArchive}
                onChange={(e) => setAutoArchive(e.target.checked)}
                className="rounded border-slate-300"
              />
              Auto-archive expired logs
            </label>
          </div>
          <div className="mt-3">
            <Button
              size="sm"
              disabled={!name.trim()}
              onClick={() => {
                createRetention.mutate(
                  { name, retention_days: days, module: module || '*', description: desc || undefined, auto_archive: autoArchive ? 1 : 0 },
                  {
                    onSuccess: () => {
                      setName('');
                      setDays(365);
                      setModule('*');
                      setDesc('');
                      setAutoArchive(false);
                      setShowForm(false);
                    },
                  },
                );
              }}
            >
              Create Policy
            </Button>
          </div>
        </div>
      )}

      {items.length > 0 ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {items.map((policy) => (
            <div key={policy.id} className={`rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900 ${!policy.is_active ? 'opacity-60' : ''}`}>
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100">{policy.name}</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400">{policy.description ?? 'No description'}</p>
                </div>
                <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${policy.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'}`}>
                  {policy.is_active ? 'Active' : 'Inactive'}
                </span>
              </div>

              <div className="mt-4 grid grid-cols-2 gap-3 text-xs">
                <div>
                  <p className="font-semibold uppercase text-slate-400">Module</p>
                  <p className="font-mono text-slate-700 dark:text-slate-300">{policy.module}</p>
                </div>
                <div>
                  <p className="font-semibold uppercase text-slate-400">Retention</p>
                  <p className="font-semibold text-slate-700 dark:text-slate-300">{policy.retention_days} days</p>
                </div>
                <div>
                  <p className="font-semibold uppercase text-slate-400">Pattern</p>
                  <p className="font-mono text-slate-700 dark:text-slate-300">{policy.action_pattern}</p>
                </div>
                <div>
                  <p className="font-semibold uppercase text-slate-400">Auto Archive</p>
                  <p className="text-slate-700 dark:text-slate-300">{policy.auto_archive ? 'Yes' : 'No'}</p>
                </div>
              </div>

              {can('compliance.manage') && policy.is_active && (
                <div className="mt-4">
                  <button
                    type="button"
                    onClick={() => deleteRetention.mutate(policy.id)}
                    className="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-slate-800"
                  >
                    Deactivate
                  </button>
                </div>
              )}
            </div>
          ))}
        </div>
      ) : (
        <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">
          No retention policies configured.
        </div>
      )}
    </div>
  );
};
