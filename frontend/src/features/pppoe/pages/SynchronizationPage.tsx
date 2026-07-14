import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { detectMissingPppoe, repairPppoeSync, syncRouterPppoe, usePppoeSyncLogs } from '../api/usePppoe';
import { RouterSelector } from '../components/RouterSelector';
import { SyncStatusCard } from '../components/SyncStatusCard';
import type { PppoeSyncDiscrepancy, PppoeSyncResult } from '../types';

export const SynchronizationPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const [selectedRouterId, setSelectedRouterId] = useState<number | ''>('');
  const [syncOutcome, setSyncOutcome] = useState<PppoeSyncResult | null>(null);
  const [missingSecrets, setMissingSecrets] = useState<PppoeSyncDiscrepancy[] | null>(null);
  const [repairLogs, setRepairLogs] = useState<string[] | null>(null);

  const { data: logs = [], isLoading: logsLoading } = usePppoeSyncLogs(50, selectedRouterId ? Number(selectedRouterId) : undefined);

  const auditMutation = useMutation({
    mutationFn: (routerId: number) => syncRouterPppoe(routerId),
    onSuccess: (res) => {
      setSyncOutcome(res);
      queryClient.invalidateQueries({ queryKey: ['pppoe', 'sync-logs'] });
    },
  });

  const detectMissingQuery = useQuery({
    queryKey: ['pppoe', 'detect-missing', selectedRouterId],
    queryFn: () => detectMissingPppoe(selectedRouterId ? Number(selectedRouterId) : undefined),
    enabled: false,
  });

  const repairMutation = useMutation({
    mutationFn: (options: Record<string, unknown>) => repairPppoeSync(options),
    onSuccess: (res) => {
      setRepairLogs(res.logs);
      if (selectedRouterId) {
        auditMutation.mutate(Number(selectedRouterId));
      }
      queryClient.invalidateQueries({ queryKey: ['pppoe'] });
    },
  });

  return (
    <div className="space-y-8">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">PPPoE Synchronization & Reconciliation</h1>
          <p className="mt-1 text-sm text-slate-500">
            Audit configurations, detect manual overrides or missing secrets on MikroTik routers, and execute automated repairs.
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="secondary" onClick={() => navigate('/network/pppoe/import')}>
            Import Router Users
          </Button>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="w-full sm:w-80">
          <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Select Target Router</label>
          <RouterSelector value={selectedRouterId} onChange={setSelectedRouterId} />
        </div>
        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
          <Button
            variant="secondary"
            disabled={!selectedRouterId || auditMutation.isPending}
            onClick={() => selectedRouterId && auditMutation.mutate(Number(selectedRouterId))}
          >
            {auditMutation.isPending ? 'Auditing Router...' : 'Audit Router Secrets'}
          </Button>
          <Button
            variant="secondary"
            onClick={async () => {
              const res = await detectMissingQuery.refetch();
              if (res.data) setMissingSecrets(res.data);
            }}
          >
            {detectMissingQuery.isFetching ? 'Scanning...' : 'Detect Missing on Routers'}
          </Button>
          {can('pppoe.sync') && selectedRouterId ? (
            <Button
              className="bg-amber-600 hover:bg-amber-700"
              disabled={repairMutation.isPending}
              onClick={() => repairMutation.mutate({ router_id: selectedRouterId, repair_accounts: true })}
            >
              {repairMutation.isPending ? 'Repairing...' : 'Repair Conflicts'}
            </Button>
          ) : null}
        </div>
      </div>

      {auditMutation.error || detectMissingQuery.error || repairMutation.error ? (
        <Alert title="Operation failed" variant="danger">
          {apiErrorMessage(auditMutation.error || detectMissingQuery.error || repairMutation.error)}
        </Alert>
      ) : null}

      {repairLogs && repairLogs.length > 0 ? (
        <Alert title="Automated Repair Completed" variant="success">
          <ul className="mt-2 list-disc pl-4 text-xs space-y-1">
            {repairLogs.map((log, idx) => (
              <li key={idx}>{log}</li>
            ))}
          </ul>
        </Alert>
      ) : null}

      {selectedRouterId ? (
        <SyncStatusCard
          syncResult={syncOutcome}
          isLoading={auditMutation.isPending}
          canSync={can('pppoe.sync')}
          onAudit={() => auditMutation.mutate(Number(selectedRouterId))}
          onRepair={() => repairMutation.mutate({ router_id: selectedRouterId, repair_accounts: true })}
        />
      ) : null}

      {missingSecrets && missingSecrets.length > 0 ? (
        <div className="rounded-xl border border-red-200 bg-white p-6 shadow-sm space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-base font-semibold text-red-900">Detected Missing Secrets ({missingSecrets.length})</h3>
            <Button size="sm" onClick={() => repairMutation.mutate({ repair_accounts: true })}>
              Repair All Missing
            </Button>
          </div>
          <p className="text-sm text-slate-500">
            The following accounts exist as active in the SkyFi database but were deleted or missing on the physical MikroTik routers:
          </p>
          <ul className="divide-y divide-slate-100 text-sm">
            {missingSecrets.map((m, idx) => (
              <li key={idx} className="py-2.5 flex items-center justify-between">
                <div>
                  <span className="font-semibold text-slate-900">{m.username}</span> — <span className="text-slate-600">{m.message}</span>
                </div>
                <span className="rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Missing</span>
              </li>
            ))}
          </ul>
        </div>
      ) : null}

      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-200 px-6 py-4">
          <h3 className="text-base font-semibold text-slate-900">Recent Synchronization Logs</h3>
          <p className="text-sm text-slate-500">Audit trail of background checks, automated repairs, and conflict resolutions.</p>
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th className="px-4 py-3">Router & Account</th>
                <th className="px-4 py-3">Action</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Message</th>
                <th className="px-4 py-3">Timestamp</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-sm">
              {logsLoading ? (
                Array.from({ length: 4 }).map((_, idx) => (
                  <tr key={idx}>
                    <td colSpan={5} className="px-4 py-5">
                      <div className="h-5 animate-pulse rounded bg-slate-100" />
                    </td>
                  </tr>
                ))
              ) : null}
              {!logsLoading && logs.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-4 py-12 text-center text-slate-500">
                    No synchronization logs recorded yet.
                  </td>
                </tr>
              ) : null}
              {!logsLoading &&
                logs.map((log) => (
                  <tr key={log.id} className="hover:bg-slate-50">
                    <td className="px-4 py-3 font-medium text-slate-800">
                      <div>{log.router_name ?? `Router #${log.router_id}`}</div>
                      {log.account_username ? <div className="text-xs font-mono text-indigo-600">{log.account_username}</div> : null}
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-slate-600">
                      {log.action.replaceAll('_', ' ')}
                    </td>
                    <td className="px-4 py-3">
                      <span
                        className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                          log.status === 'success'
                            ? 'bg-emerald-50 text-emerald-700'
                            : log.status === 'failed'
                            ? 'bg-red-50 text-red-700'
                            : 'bg-amber-50 text-amber-700'
                        }`}
                      >
                        {log.status.toUpperCase()}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-slate-600 max-w-md truncate" title={log.message}>
                      {log.message}
                    </td>
                    <td className="px-4 py-3 text-xs text-slate-500">{log.created_at}</td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
