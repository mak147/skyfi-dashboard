import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { reconnectPppoeAccount, setPppoeAccountEnabled, syncAccountPppoe, usePppoeAccounts } from '../api/usePppoe';
import { PPPoETable } from '../components/PPPoETable';
import type { PppoeAccount, PppoeListFilters, PppoeStatus, PppoeSyncStatus } from '../types';

export const PPPoEUsersPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const [activeTab, setActiveTab] = useState<'all' | 'active' | 'out_of_sync'>('all');

  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const sort = searchParams.get('sort') ?? '-created_at';
  const search = searchParams.get('search') || undefined;

  const filters: PppoeListFilters = {
    page,
    perPage: 15,
    sort,
    search,
    status: activeTab === 'active' ? 'active' : (searchParams.get('status') as PppoeStatus | '') || undefined,
    sync_status: activeTab === 'out_of_sync' ? 'out_of_sync' : (searchParams.get('sync_status') as PppoeSyncStatus | '') || undefined,
  };

  const { data: accountsResponse, isLoading, error } = usePppoeAccounts(filters);

  const toggleMutation = useMutation({
    mutationFn: (acc: PppoeAccount) => setPppoeAccountEnabled(acc.id, acc.status !== 'active'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe'] }),
  });

  const reconnectMutation = useMutation({
    mutationFn: (acc: PppoeAccount) => reconnectPppoeAccount(acc.id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe'] }),
  });

  const syncMutation = useMutation({
    mutationFn: (acc: PppoeAccount) => syncAccountPppoe(acc.id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe'] }),
  });

  const accounts = accountsResponse?.data.map((i) => i.attributes) ?? [];
  const meta = accountsResponse?.meta;

  const updateSearch = (value: string) => {
    const params = new URLSearchParams(searchParams);
    if (value) params.set('search', value);
    else params.delete('search');
    params.set('page', '1');
    setSearchParams(params, { replace: true });
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">PPPoE Accounts & Secrets</h1>
          <p className="mt-1 text-sm text-slate-500">
            Enterprise PPPoE subscriber management linked to MikroTik RouterOS profiles and live active sessions.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {can('pppoe.monitor') ? (
            <Button variant="secondary" onClick={() => navigate('/network/pppoe/sessions/active')}>
              Live Sessions
            </Button>
          ) : null}
          {can('pppoe.sync') ? (
            <Button variant="secondary" onClick={() => navigate('/network/pppoe/sync')}>
              Sync Audit & Repair
            </Button>
          ) : null}
          {can('pppoe.create') ? (
            <Button onClick={() => navigate('/network/pppoe/accounts/new')}>Create User</Button>
          ) : null}
        </div>
      </div>

      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex gap-2 border-b border-slate-200 sm:border-0">
          <button
            type="button"
            className={`px-4 py-2 text-sm font-semibold rounded-t-lg sm:rounded-lg ${
              activeTab === 'all' ? 'bg-indigo-50 text-indigo-700 sm:ring-1 sm:ring-indigo-100' : 'text-slate-600 hover:bg-slate-50'
            }`}
            onClick={() => setActiveTab('all')}
          >
            All Accounts
          </button>
          <button
            type="button"
            className={`px-4 py-2 text-sm font-semibold rounded-t-lg sm:rounded-lg ${
              activeTab === 'active' ? 'bg-indigo-50 text-indigo-700 sm:ring-1 sm:ring-indigo-100' : 'text-slate-600 hover:bg-slate-50'
            }`}
            onClick={() => setActiveTab('active')}
          >
            Active Users
          </button>
          <button
            type="button"
            className={`px-4 py-2 text-sm font-semibold rounded-t-lg sm:rounded-lg ${
              activeTab === 'out_of_sync' ? 'bg-indigo-50 text-indigo-700 sm:ring-1 sm:ring-indigo-100' : 'text-slate-600 hover:bg-slate-50'
            }`}
            onClick={() => setActiveTab('out_of_sync')}
          >
            Out-of-Sync / Discrepancies
          </button>
        </div>

        <div className="w-full sm:w-72">
          <input
            type="text"
            placeholder="Search username, IP, caller ID..."
            defaultValue={search ?? ''}
            onBlur={(e) => updateSearch(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && updateSearch((e.target as HTMLInputElement).value)}
            className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
      </div>

      {error ? (
        <Alert title="Unable to load PPPoE accounts" variant="danger">
          {apiErrorMessage(error)}
        </Alert>
      ) : null}

      {toggleMutation.error ? (
        <Alert title="Status update failed" variant="danger">
          {apiErrorMessage(toggleMutation.error)}
        </Alert>
      ) : null}

      {reconnectMutation.error ? (
        <Alert title="Reconnection failed" variant="danger">
          {apiErrorMessage(reconnectMutation.error)}
        </Alert>
      ) : null}

      <PPPoETable
        accounts={accounts}
        isLoading={isLoading}
        canUpdate={can('pppoe.update') || can('pppoe.enable')}
        canManage={can('pppoe.manage')}
        canSync={can('pppoe.sync')}
        onToggleStatus={(acc) => toggleMutation.mutate(acc)}
        onReconnect={(acc) => reconnectMutation.mutate(acc)}
        onSync={(acc) => syncMutation.mutate(acc)}
      />

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between border-t border-slate-200 pt-4">
          <p className="text-sm text-slate-500">
            Showing page <span className="font-semibold">{meta.current_page}</span> of{' '}
            <span className="font-semibold">{meta.last_page}</span> ({meta.total} total)
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={meta.current_page <= 1}
              onClick={() => {
                const params = new URLSearchParams(searchParams);
                params.set('page', String(meta.current_page - 1));
                setSearchParams(params, { replace: true });
              }}
            >
              Previous
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={meta.current_page >= meta.last_page}
              onClick={() => {
                const params = new URLSearchParams(searchParams);
                params.set('page', String(meta.current_page + 1));
                setSearchParams(params, { replace: true });
              }}
            >
              Next
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
