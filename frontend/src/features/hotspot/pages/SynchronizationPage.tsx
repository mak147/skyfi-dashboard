import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import {
  syncHotspotRouter,
  repairHotspotSync,
  useHotspotSyncLogs,
  importHotspotUsers,
  importHotspotProfiles,
} from '../api/useHotspot';
import { SyncStatusCard } from '../components/SyncStatusCard';
import { RouterSelector } from '../components/RouterSelector';
import type { HotspotSyncResult } from '../types';

export const SynchronizationPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [routerId, setRouterId] = useState('');
  const [syncResult, setSyncResult] = useState<HotspotSyncResult | null>(null);
  const [repairResult, setRepairResult] = useState<{
    repaired_count: number;
    failed_count: number;
    logs: string[];
  } | null>(null);
  const [importResult, setImportResult] = useState<{
    imported_count: number;
    failed_count: number;
    errors: string[];
  } | null>(null);

  const { data: syncLogs = [] } = useHotspotSyncLogs(30, routerId ? Number(routerId) : undefined);

  const syncMutation = useMutation({
    mutationFn: (rid: number) => syncHotspotRouter(rid),
    onSuccess: (result) => {
      setSyncResult(result);
      queryClient.invalidateQueries({ queryKey: ['hotspot'] });
    },
  });

  const repairMutation = useMutation({
    mutationFn: (options: Record<string, unknown>) => repairHotspotSync(options),
    onSuccess: (result) => {
      setRepairResult(result);
      queryClient.invalidateQueries({ queryKey: ['hotspot'] });
    },
  });

  const importMutation = useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      importHotspotUsers(data as Parameters<typeof importHotspotUsers>[0]),
    onSuccess: (result) => {
      setImportResult(result);
      queryClient.invalidateQueries({ queryKey: ['hotspot'] });
    },
  });

  const importProfilesMutation = useMutation({
    mutationFn: (rid: number) => importHotspotProfiles(rid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'profiles'] });
    },
  });

  const handleSync = () => {
    if (!routerId) return;
    setSyncResult(null);
    setRepairResult(null);
    syncMutation.mutate(Number(routerId));
  };

  const handleRepair = () => {
    const options: Record<string, unknown> = {};
    if (routerId) options.router_id = Number(routerId);
    repairMutation.mutate(options);
  };

  const handleImport = () => {
    if (!routerId) return;
    importMutation.mutate({ router_id: Number(routerId), overwrite_conflicts: false });
  };

  const handleImportProfiles = () => {
    if (!routerId) return;
    importProfilesMutation.mutate(Number(routerId));
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Hotspot Synchronization</h1>
          <p className="mt-1 text-sm text-slate-500">
            Audit, repair, and import hotspot users and profiles between SkyFi and MikroTik routers.
          </p>
        </div>
        <Button variant="secondary" onClick={() => navigate('/hotspot')}>
          Back to Users
        </Button>
      </div>

      {/* Router Selection using RouterSelector */}
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
          <div className="flex-1">
            <RouterSelector value={routerId} onChange={setRouterId} />
          </div>
          <div className="flex flex-wrap gap-2">
            <Button onClick={handleSync} disabled={!routerId || syncMutation.isPending}>
              {syncMutation.isPending ? 'Auditing...' : 'Audit Router'}
            </Button>
            <Button variant="secondary" onClick={handleRepair} disabled={repairMutation.isPending}>
              {repairMutation.isPending ? 'Repairing...' : 'Repair All'}
            </Button>
            <Button variant="secondary" onClick={handleImport} disabled={!routerId || importMutation.isPending}>
              Import Users
            </Button>
            <Button
              variant="secondary"
              onClick={handleImportProfiles}
              disabled={!routerId || importProfilesMutation.isPending}
            >
              Import Profiles
            </Button>
          </div>
        </div>
      </div>

      {/* Errors */}
      {syncMutation.error ? (
        <Alert title="Sync failed" variant="danger">
          {apiErrorMessage(syncMutation.error)}
        </Alert>
      ) : null}
      {repairMutation.error ? (
        <Alert title="Repair failed" variant="danger">
          {apiErrorMessage(repairMutation.error)}
        </Alert>
      ) : null}
      {importMutation.error ? (
        <Alert title="Import failed" variant="danger">
          {apiErrorMessage(importMutation.error)}
        </Alert>
      ) : null}

      {/* Import Results */}
      {importResult ? (
        <div className="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
          <p className="text-sm font-semibold text-indigo-800">Import Complete</p>
          <p className="text-sm text-indigo-700">
            Imported: {importResult.imported_count} | Failed: {importResult.failed_count}
          </p>
          {importResult.errors.length > 0 ? (
            <ul className="mt-2 text-xs text-indigo-600 space-y-1">
              {importResult.errors.slice(0, 10).map((e, i) => (
                <li key={i}>• {e}</li>
              ))}
            </ul>
          ) : null}
        </div>
      ) : null}

      {/* Repair Results */}
      {repairResult ? (
        <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
          <p className="text-sm font-semibold text-amber-800">Repair Complete</p>
          <p className="text-sm text-amber-700">
            Repaired: {repairResult.repaired_count} | Failed: {repairResult.failed_count}
          </p>
          {repairResult.logs.length > 0 ? (
            <ul className="mt-2 text-xs text-amber-600 space-y-1 max-h-40 overflow-y-auto">
              {repairResult.logs.map((l, i) => (
                <li key={i}>• {l}</li>
              ))}
            </ul>
          ) : null}
        </div>
      ) : null}

      {/* Sync Status Card — using dedicated component */}
      <SyncStatusCard
        syncResult={syncResult}
        isLoading={syncMutation.isPending}
        onAudit={handleSync}
        onRepair={handleRepair}
        canSync={!!routerId}
      />

      {/* Sync Logs */}
      {syncLogs.length > 0 ? (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h3 className="text-sm font-semibold text-slate-900 mb-3">Recent Sync Logs</h3>
          <div className="space-y-2 max-h-80 overflow-y-auto">
            {syncLogs.map((log) => (
              <div key={log.id} className="flex items-start gap-3 rounded-lg bg-slate-50 p-3">
                <span
                  className={`mt-0.5 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                    log.status === 'success'
                      ? 'bg-emerald-100 text-emerald-700'
                      : log.status === 'failed'
                      ? 'bg-red-100 text-red-700'
                      : 'bg-amber-100 text-amber-700'
                  }`}
                >
                  {log.status}
                </span>
                <div className="flex-1 min-w-0">
                  <p className="text-sm text-slate-800 truncate">{log.message}</p>
                  <p className="text-xs text-slate-500">
                    {log.action} • {log.router_name ?? `Router #${log.router_id}`} • {log.created_at}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      ) : null}
    </div>
  );
};
