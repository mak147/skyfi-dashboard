import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import {
  reconnectPppoeAccount,
  resetPppoePassword,
  setPppoeAccountEnabled,
  suspendPppoeAccount,
  syncAccountPppoe,
  useAccountStatistics,
  usePppoeAccount,
  useSessionHistory,
} from '../api/usePppoe';
import { SessionTimeline } from '../components/SessionTimeline';
import { UsageStatistics } from '../components/UsageStatistics';
import type { PppoeSyncResult } from '../types';

export const PPPoEDetailsPage = () => {
  const { id } = useParams<{ id: string }>();
  const accountId = Number(id);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();

  const { data: account, isLoading, error } = usePppoeAccount(accountId);
  const { data: stats, isLoading: statsLoading } = useAccountStatistics(accountId);
  const { data: historyResponse, isLoading: historyLoading } = useSessionHistory(1, 15, accountId);

  const [newPassword, setNewPassword] = useState('');
  const [resetSuccess, setResetSuccess] = useState(false);
  const [syncOutcome, setSyncOutcome] = useState<PppoeSyncResult | null>(null);

  const toggleMutation = useMutation({
    mutationFn: () => setPppoeAccountEnabled(accountId, account?.status !== 'active'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe', 'account', accountId] }),
  });

  const suspendMutation = useMutation({
    mutationFn: () => suspendPppoeAccount(accountId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe', 'account', accountId] }),
  });

  const reconnectMutation = useMutation({
    mutationFn: () => reconnectPppoeAccount(accountId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe', 'account', accountId] }),
  });

  const resetPasswordMutation = useMutation({
    mutationFn: (pwd: string) => resetPppoePassword(accountId, { password: pwd }),
    onSuccess: () => {
      setResetSuccess(true);
      setNewPassword('');
      queryClient.invalidateQueries({ queryKey: ['pppoe', 'account', accountId] });
    },
  });

  const syncMutation = useMutation({
    mutationFn: () => syncAccountPppoe(accountId),
    onSuccess: (res) => {
      setSyncOutcome(res);
      queryClient.invalidateQueries({ queryKey: ['pppoe', 'account', accountId] });
    },
  });

  if (isLoading) {
    return (
      <div className="space-y-6 animate-pulse">
        <div className="h-10 w-1/3 rounded bg-slate-200" />
        <div className="h-64 rounded-xl bg-slate-100" />
      </div>
    );
  }

  if (error || !account) {
    return (
      <Alert title="Unable to load account" variant="danger">
        {apiErrorMessage(error) ?? 'Account not found.'}
      </Alert>
    );
  }

  const history = historyResponse?.items ?? [];

  return (
    <div className="space-y-8">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold tracking-tight text-slate-900">{account.username}</h1>
            <span
              className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                account.status === 'active'
                  ? 'bg-emerald-50 text-emerald-700'
                  : account.status === 'suspended'
                  ? 'bg-amber-50 text-amber-700'
                  : 'bg-slate-100 text-slate-600'
              }`}
            >
              {account.status.toUpperCase()}
            </span>
          </div>
          <p className="mt-1 text-sm text-slate-500">
            Linked to <strong className="text-slate-800">{account.customer_name ?? `Customer #${account.customer_id}`}</strong>{' '}
            | Connection <span className="font-mono text-indigo-600">{account.connection_number ?? `#${account.connection_id}`}</span>
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {can('pppoe.update') ? (
            <Button variant="secondary" onClick={() => navigate(`/network/pppoe/accounts/${accountId}/edit`)}>
              Edit Configuration
            </Button>
          ) : null}
          {can('pppoe.manage') && account.status === 'active' ? (
            <Button
              variant="secondary"
              className="text-amber-700 hover:bg-amber-50"
              onClick={() => reconnectMutation.mutate()}
            >
              Force Reconnect
            </Button>
          ) : null}
          {can('pppoe.manage') && account.status === 'active' ? (
            <Button
              variant="secondary"
              className="text-red-700 hover:bg-red-50"
              onClick={() => suspendMutation.mutate()}
            >
              Suspend
            </Button>
          ) : null}
          {can('pppoe.update') ? (
            <Button
              variant="secondary"
              onClick={() => toggleMutation.mutate()}
            >
              {account.status === 'active' ? 'Disable Account' : 'Enable Account'}
            </Button>
          ) : null}
          {can('pppoe.sync') ? (
            <Button onClick={() => syncMutation.mutate()}>
              {syncMutation.isPending ? 'Syncing...' : 'Sync with Router'}
            </Button>
          ) : null}
        </div>
      </div>

      {toggleMutation.error || suspendMutation.error || reconnectMutation.error || syncMutation.error ? (
        <Alert title="Operation failed" variant="danger">
          {apiErrorMessage(toggleMutation.error || suspendMutation.error || reconnectMutation.error || syncMutation.error)}
        </Alert>
      ) : null}

      {syncOutcome ? (
        <div className={`rounded-xl border p-4 ${syncOutcome.status === 'synced' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'}`}>
          <p className="font-semibold text-sm">Sync Audit Outcome: {syncOutcome.status.toUpperCase()}</p>
          <p className="text-xs mt-1">
            {syncOutcome.discrepancies.length === 0
              ? 'Secret matches 100% between SkyFi database and RouterOS.'
              : `${syncOutcome.discrepancies.length} discrepancy found: ${syncOutcome.discrepancies[0]?.message}`}
          </p>
        </div>
      ) : null}

      <div className="grid gap-6 md:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 className="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3">Network & Router Identity</h3>
          <dl className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <dt className="text-xs text-slate-400">Target Router</dt>
              <dd className="mt-1 font-semibold text-slate-800">{account.router_name ?? `Router #${account.router_id}`}</dd>
            </div>
            <div>
              <dt className="text-xs text-slate-400">RouterOS Profile</dt>
              <dd className="mt-1 font-mono text-indigo-700 font-semibold">{account.profile}</dd>
            </div>
            <div>
              <dt className="text-xs text-slate-400">Service Type</dt>
              <dd className="mt-1 font-mono text-slate-700">{account.service}</dd>
            </div>
            <div>
              <dt className="text-xs text-slate-400">Assigned Package</dt>
              <dd className="mt-1 font-medium text-slate-800">{account.package_name ?? `Package #${account.package_id}`}</dd>
            </div>
          </dl>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 className="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3">Bindings & Limits</h3>
          <dl className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <dt className="text-xs text-slate-400">Static IP Assignment</dt>
              <dd className="mt-1 font-mono text-emerald-700 font-medium">{account.static_ip ?? 'Pool assigned'}</dd>
            </div>
            <div>
              <dt className="text-xs text-slate-400">MAC Binding</dt>
              <dd className="mt-1 font-mono text-slate-700">{account.mac_binding ?? 'None'}</dd>
            </div>
            <div>
              <dt className="text-xs text-slate-400">Caller ID Tag</dt>
              <dd className="mt-1 font-mono text-slate-700">{account.caller_id ?? 'None'}</dd>
            </div>
            <div>
              <dt className="text-xs text-slate-400">Custom Rate Limit</dt>
              <dd className="mt-1 font-mono text-slate-800 font-medium">{account.rate_limit ?? 'From profile queue'}</dd>
            </div>
          </dl>
        </div>
      </div>

      <UsageStatistics stats={stats} isLoading={statsLoading} />

      <SessionTimeline history={history} isLoading={historyLoading} />

      {can('pppoe.update') ? (
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 className="text-base font-semibold text-slate-900">Reset Account Password</h3>
          <p className="text-sm text-slate-500">
            Generating or resetting the password updates the encrypted secret in the SkyFi database and immediately pushes the new password to the MikroTik router via <code className="text-indigo-600">/ppp/secret/set =password=...</code>.
          </p>
          {resetSuccess ? (
            <Alert title="Password updated" variant="success">
              The PPPoE password was successfully changed and pushed to the router.
            </Alert>
          ) : null}
          {resetPasswordMutation.error ? (
            <Alert title="Reset failed" variant="danger">
              {apiErrorMessage(resetPasswordMutation.error)}
            </Alert>
          ) : null}
          <div className="flex flex-col sm:flex-row gap-3 max-w-md">
            <input
              type="password"
              placeholder="Enter new PPPoE password..."
              value={newPassword}
              onChange={(e) => {
                setNewPassword(e.target.value);
                setResetSuccess(false);
              }}
              className="h-10 flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <Button
              disabled={newPassword.length < 6 || resetPasswordMutation.isPending}
              onClick={() => resetPasswordMutation.mutate(newPassword)}
            >
              {resetPasswordMutation.isPending ? 'Updating...' : 'Push New Password'}
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
