import { Button } from '@/components/ui/button';
import type { HotspotSyncResult } from '../types';

interface SyncStatusCardProps {
  syncResult: HotspotSyncResult | null;
  isLoading: boolean;
  onAudit: () => void;
  onRepair: () => void;
  canSync: boolean;
}

export const SyncStatusCard = ({ syncResult, isLoading, onAudit, onRepair, canSync }: SyncStatusCardProps) => {
  if (isLoading) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm animate-pulse">
        <div className="h-6 w-1/3 rounded bg-slate-200 mb-4" />
        <div className="h-20 rounded bg-slate-100" />
      </div>
    );
  }

  if (!syncResult) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex items-center justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-900">Hotspot Synchronization Status</h3>
          <p className="text-sm text-slate-500 mt-1">
            Run an audit to check for profile mismatches, missing users, or orphan entries on MikroTik routers.
          </p>
        </div>
        {canSync ? <Button onClick={onAudit}>Audit Now</Button> : null}
      </div>
    );
  }

  const isSynced = syncResult.status === 'synced' && syncResult.discrepancies.length === 0;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
      <div className="flex items-start justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="text-base font-semibold text-slate-900">
              Audit Summary: {syncResult.router_name}
            </h3>
            <span
              className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                isSynced ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
              }`}
            >
              {isSynced ? '✓ 100% Synced' : '⚠️ Discrepancies Found'}
            </span>
          </div>
          <p className="text-sm text-slate-500 mt-1">
            Checked at {syncResult.checked_at ?? 'just now'}. SkyFi DB:{' '}
            <strong className="text-slate-800">{syncResult.total_users_in_db}</strong> users | RouterOS:{' '}
            <strong className="text-slate-800">{syncResult.total_users_on_router}</strong> users.
          </p>
        </div>
        <div className="flex gap-2 shrink-0">
          {canSync ? (
            <>
              <Button variant="secondary" onClick={onAudit}>
                Re-audit
              </Button>
              {!isSynced ? (
                <Button onClick={onRepair} className="bg-amber-600 hover:bg-amber-700">
                  Repair Conflicts
                </Button>
              ) : null}
            </>
          ) : null}
        </div>
      </div>

      {!isSynced && syncResult.discrepancies.length > 0 ? (
        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50/50 p-4 space-y-2 max-h-64 overflow-y-auto">
          <p className="text-xs font-semibold uppercase tracking-wider text-amber-800">
            Identified Discrepancies ({syncResult.discrepancies.length})
          </p>
          <ul className="space-y-1.5 text-sm text-amber-900 divide-y divide-amber-200/60">
            {syncResult.discrepancies.map((d, idx) => (
              <li key={idx} className="pt-1.5 flex items-start justify-between gap-4">
                <div>
                  <span className="font-semibold">{d.username}</span> — {d.message}
                </div>
                <span className="shrink-0 rounded bg-amber-200/80 px-1.5 py-0.5 text-xs font-mono font-medium text-amber-900">
                  {d.type.replaceAll('_', ' ')}
                </span>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </div>
  );
};
