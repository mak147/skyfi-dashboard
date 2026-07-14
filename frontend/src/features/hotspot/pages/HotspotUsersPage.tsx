import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { setHotspotUserEnabled, suspendHotspotUser, syncHotspotUser, useHotspotUsers } from '../api/useHotspot';
import { HotspotUserTable } from '../components/HotspotUserTable';
import type { HotspotUser, HotspotUserListFilters, HotspotUserStatus, HotspotSyncStatus } from '../types';

export const HotspotUsersPage = () => {
  const [searchParams, setSearchParams] = useState<URLSearchParams>(() => new URLSearchParams(window.location.search));
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const [activeTab, setActiveTab] = useState<'all' | 'active' | 'out_of_sync'>('all');

  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const sort = searchParams.get('sort') ?? '-created_at';
  const search = searchParams.get('search') || undefined;

  const filters: HotspotUserListFilters = {
    page,
    perPage: 15,
    sort,
    search,
    status: activeTab === 'active' ? 'active' : (searchParams.get('status') as HotspotUserStatus | '') || undefined,
    sync_status: activeTab === 'out_of_sync' ? 'out_of_sync' : (searchParams.get('sync_status') as HotspotSyncStatus | '') || undefined,
  };

  const { data: usersResponse, isLoading, error } = useHotspotUsers(filters);

  const toggleMutation = useMutation({
    mutationFn: (user: HotspotUser) => setHotspotUserEnabled(user.id, user.status !== 'active'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot'] }),
  });

  const suspendMutation = useMutation({
    mutationFn: (user: HotspotUser) => suspendHotspotUser(user.id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot'] }),
  });

  const syncMutation = useMutation({
    mutationFn: (user: HotspotUser) => syncHotspotUser(user.id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot'] }),
  });

  const users = usersResponse?.data.map((i) => i.attributes) ?? [];
  const meta = usersResponse?.meta;

  const updateSearch = (value: string) => {
    const params = new URLSearchParams(searchParams);
    if (value) params.set('search', value);
    else params.delete('search');
    params.set('page', '1');
    setSearchParams(params);
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Hotspot Users</h1>
          <p className="mt-1 text-sm text-slate-500">Manage hotspot users, vouchers, and MikroTik captive portal access.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {can('hotspot.monitor') ? (
            <Button variant="secondary" onClick={() => navigate('/hotspot/sessions/active')}>Live Sessions</Button>
          ) : null}
          {can('hotspot.sync') ? (
            <Button variant="secondary" onClick={() => navigate('/hotspot/sync')}>Sync & Repair</Button>
          ) : null}
          {can('hotspot.vouchers') ? (
            <Button variant="secondary" onClick={() => navigate('/hotspot/vouchers')}>Vouchers</Button>
          ) : null}
          {can('hotspot.create') ? (
            <Button onClick={() => navigate('/hotspot/users/new')}>Create User</Button>
          ) : null}
        </div>
      </div>

      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex gap-2 border-b border-slate-200 sm:border-0">
          {(['all', 'active', 'out_of_sync'] as const).map((tab) => (
            <button
              key={tab}
              type="button"
              className={`px-4 py-2 text-sm font-semibold rounded-t-lg sm:rounded-lg ${
                activeTab === tab ? 'bg-indigo-50 text-indigo-700 sm:ring-1 sm:ring-indigo-100' : 'text-slate-600 hover:bg-slate-50'
              }`}
              onClick={() => setActiveTab(tab)}
            >
              {tab === 'all' ? 'All Users' : tab === 'active' ? 'Active Users' : 'Out-of-Sync'}
            </button>
          ))}
        </div>

        <div className="w-full sm:w-72">
          <input
            type="text"
            placeholder="Search username, profile, MAC..."
            defaultValue={search ?? ''}
            onBlur={(e) => updateSearch(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && updateSearch((e.target as HTMLInputElement).value)}
            className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
      </div>

      {error ? <Alert title="Unable to load hotspot users" variant="danger">{apiErrorMessage(error)}</Alert> : null}
      {toggleMutation.error ? <Alert title="Status update failed" variant="danger">{apiErrorMessage(toggleMutation.error)}</Alert> : null}

      <HotspotUserTable
        users={users}
        isLoading={isLoading}
        canUpdate={can('hotspot.update')}
        canManage={can('hotspot.manage')}
        canSync={can('hotspot.sync')}
        onToggleStatus={(user) => toggleMutation.mutate(user)}
        onSuspend={(user) => suspendMutation.mutate(user)}
        onSync={(user) => syncMutation.mutate(user)}
      />

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between border-t border-slate-200 pt-4">
          <p className="text-sm text-slate-500">
            Page <span className="font-semibold">{meta.current_page}</span> of <span className="font-semibold">{meta.last_page}</span> ({meta.total} total)
          </p>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => {
              const params = new URLSearchParams(searchParams);
              params.set('page', String(meta.current_page - 1));
              setSearchParams(params);
            }}>Previous</Button>
            <Button size="sm" variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => {
              const params = new URLSearchParams(searchParams);
              params.set('page', String(meta.current_page + 1));
              setSearchParams(params);
            }}>Next</Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
