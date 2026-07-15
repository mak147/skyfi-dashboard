import { useState } from 'react';

import { Button } from '@/components/ui/button';

import { useCreateApiKey, useDeleteApiKey, useRegenerateApiKey } from '../api/useIntegration';
import type { ApiKeyItem, PageMeta } from '../types';

interface Props {
  items: ApiKeyItem[];
  meta?: PageMeta;
  onPageChange?: (page: number) => void;
}

export const ApiKeyTable = ({ items, meta, onPageChange }: Props) => {
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState('');
  const [newScopes, setNewScopes] = useState('customers.read,billing.read');
  const [revealedKey, setRevealedKey] = useState<string | null>(null);
  const create = useCreateApiKey();
  const deleteKey = useDeleteApiKey();
  const regenerate = useRegenerateApiKey();

  const handleCreate = () => {
    if (!newName || !newScopes) return;
    create.mutate(
      { name: newName, scopes: newScopes.split(',').map((s) => s.trim()) } as Partial<ApiKeyItem>,
      {
        onSuccess: (res) => {
          const plain = (res as { meta?: { plain_text_key?: string } }).meta?.plain_text_key;
          if (plain) setRevealedKey(plain);
          setNewName('');
          setShowCreate(false);
        },
      },
    );
  };

  return (
    <div className="space-y-4">
      <div className="flex justify-between">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">API Keys</h2>
        <Button size="sm" onClick={() => setShowCreate(!showCreate)}>
          {showCreate ? 'Cancel' : 'Create Key'}
        </Button>
      </div>

      {revealedKey && (
        <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950">
          <p className="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
            Copy this key now — it won&apos;t be shown again
          </p>
          <code className="mt-1 block break-all rounded bg-white p-2 text-sm text-slate-800 dark:bg-slate-900 dark:text-slate-200">
            {revealedKey}
          </code>
          <Button size="sm" variant="secondary" className="mt-2" onClick={() => setRevealedKey(null)}>
            Dismiss
          </Button>
        </div>
      )}

      {showCreate && (
        <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 space-y-3">
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Name</label>
            <input className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newName} onChange={(e) => setNewName(e.target.value)} placeholder="e.g. Mobile App Key" />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Scopes (comma-separated)</label>
            <input className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" value={newScopes} onChange={(e) => setNewScopes(e.target.value)} />
          </div>
          <Button size="sm" disabled={create.isPending} onClick={handleCreate}>
            {create.isPending ? 'Creating...' : 'Create'}
          </Button>
        </div>
      )}

      <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 dark:bg-slate-800">
            <tr>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Name</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Prefix</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Scopes</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Status</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Last Used</th>
              <th className="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
            {items.map((key) => (
              <tr key={key.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">{key.name}</td>
                <td className="px-4 py-3 font-mono text-xs text-slate-500">{key.key_prefix}•••</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                  <div className="flex flex-wrap gap-1">
                    {key.scopes.slice(0, 3).map((s) => (
                      <span key={s} className="inline-block rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{s}</span>
                    ))}
                    {key.scopes.length > 3 && <span className="text-xs text-slate-400">+{key.scopes.length - 3}</span>}
                  </div>
                </td>
                <td className="px-4 py-3">
                  <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${key.is_active ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'}`}>
                    {key.is_active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="px-4 py-3 text-slate-500">{key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : 'Never'}</td>
                <td className="px-4 py-3 text-right space-x-2">
                  <Button size="sm" variant="secondary" disabled={regenerate.isPending} onClick={() => { regenerate.mutate(key.id); }}>
                    Regenerate
                  </Button>
                  <Button size="sm" variant="secondary" disabled={deleteKey.isPending} onClick={() => { if (confirm('Revoke this key?')) deleteKey.mutate(key.id); }}>
                    Revoke
                  </Button>
                </td>
              </tr>
            ))}
            {items.length === 0 && (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-400">No API keys yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {meta && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-slate-500">{meta.total} keys</span>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => onPageChange?.(meta.current_page - 1)}>Prev</Button>
            <Button size="sm" variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange?.(meta.current_page + 1)}>Next</Button>
          </div>
        </div>
      )}
    </div>
  );
};
