import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useHotspotProfiles, deleteHotspotProfile } from '../api/useHotspot';
import type { HotspotProfile } from '../types';

export const HotspotProfilesPage = () => {
  const navigate = useNavigate();
  const { can } = usePermissions();
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  const { data: response, isLoading, error } = useHotspotProfiles(page, 15, search || undefined);

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteHotspotProfile(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot', 'profiles'] }),
  });

  const profiles = response?.data.map((i) => i.attributes) ?? [];
  const meta = response?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Hotspot Profiles</h1>
          <p className="mt-1 text-sm text-slate-500">Manage hotspot user profiles with speed limits, timeouts, and login methods.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => navigate('/hotspot')}>Back to Users</Button>
          {can('hotspot.create') ? (
            <Button onClick={() => navigate('/hotspot/profiles/new')}>Create Profile</Button>
          ) : null}
        </div>
      </div>

      <div className="w-full sm:w-72">
        <input
          type="text"
          placeholder="Search profiles..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>

      {error ? <Alert title="Unable to load profiles" variant="danger">{apiErrorMessage(error)}</Alert> : null}
      {deleteMutation.error ? <Alert title="Delete failed" variant="danger">{apiErrorMessage(deleteMutation.error)}</Alert> : null}

      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3">Router</th>
                <th className="px-4 py-3">Rate Limits</th>
                <th className="px-4 py-3">Timeouts</th>
                <th className="px-4 py-3">Shared Users</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {isLoading
                ? Array.from({ length: 3 }).map((_, i) => (
                    <tr key={i}><td colSpan={7} className="px-4 py-5"><div className="h-5 animate-pulse rounded bg-slate-100" /></td></tr>
                  ))
                : null}
              {!isLoading && profiles.length === 0 ? (
                <tr><td colSpan={7} className="px-4 py-12 text-center text-sm text-slate-500">No hotspot profiles found.</td></tr>
              ) : null}
              {!isLoading && profiles.map((p: HotspotProfile) => (
                <tr key={p.id} className="transition hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-900">{p.name}</p>
                    <p className="font-mono text-xs text-indigo-600">{p.router_profile_name}</p>
                  </td>
                  <td className="px-4 py-3 text-sm text-slate-600">{p.router_name ?? `Router #${p.router_id}`}</td>
                  <td className="px-4 py-3 text-xs text-slate-600">
                    {p.rate_limit_down || p.rate_limit_up ? (
                      <span>↓{p.rate_limit_down ?? '?'} / ↑{p.rate_limit_up ?? '?'}</span>
                    ) : <span className="text-slate-400">Unlimited</span>}
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-600">
                    Session: {p.session_timeout ? `${p.session_timeout}s` : '∞'}
                    {' / '}Idle: {p.idle_timeout ? `${p.idle_timeout}s` : '∞'}
                  </td>
                  <td className="px-4 py-3 text-sm font-semibold text-slate-800">{p.shared_users}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${p.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                      {p.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex items-center justify-end gap-1.5">
                      {can('hotspot.update') ? (
                        <Button size="sm" variant="secondary" onClick={() => navigate(`/hotspot/profiles/${p.id}/edit`)}>Edit</Button>
                      ) : null}
                      {can('hotspot.delete') ? (
                        <Button size="sm" variant="secondary" className="text-red-700" onClick={() => {
                          if (confirm('Delete this hotspot profile?')) deleteMutation.mutate(p.id);
                        }}>Delete</Button>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between border-t border-slate-200 pt-4">
          <p className="text-sm text-slate-500">Page {meta.current_page} of {meta.last_page}</p>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Previous</Button>
            <Button size="sm" variant="secondary" disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next</Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
