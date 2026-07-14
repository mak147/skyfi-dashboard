import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import type { HotspotUser } from '../types';

interface HotspotUserTableProps {
  users: HotspotUser[];
  isLoading: boolean;
  canUpdate: boolean;
  canManage: boolean;
  canSync: boolean;
  onToggleStatus: (user: HotspotUser) => void;
  onSuspend: (user: HotspotUser) => void;
  onSync: (user: HotspotUser) => void;
}

export const HotspotUserTable = ({ users, isLoading, canUpdate, canManage, canSync, onToggleStatus, onSuspend, onSync }: HotspotUserTableProps) => {
  const navigate = useNavigate();

  const getStatusBadge = (status: HotspotUser['status']) => {
    switch (status) {
      case 'active':
        return <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Active</span>;
      case 'disabled':
        return <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Disabled</span>;
      case 'suspended':
        return <span className="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Suspended</span>;
      default:
        return <span className="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">Pending</span>;
    }
  };

  const getSyncBadge = (syncStatus: HotspotUser['sync_status']) => {
    switch (syncStatus) {
      case 'synced':
        return <span className="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-800">✓ Synced</span>;
      case 'missing_on_router':
        return <span className="inline-flex items-center gap-1 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-800">⚠️ Missing</span>;
      case 'conflict':
        return <span className="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800">⚡ Conflict</span>;
      default:
        return <span className="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">Out of Sync</span>;
    }
  };

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3">Username & Customer</th>
              <th className="px-4 py-3">Router & Profile</th>
              <th className="px-4 py-3">Limits</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Sync</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {isLoading
              ? Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td colSpan={6} className="px-4 py-5">
                      <div className="h-5 animate-pulse rounded bg-slate-100" />
                    </td>
                  </tr>
                ))
              : null}
            {!isLoading && users.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-sm text-slate-500">
                  No hotspot users match the current filters.
                </td>
              </tr>
            ) : null}
            {!isLoading &&
              users.map((user) => (
                <tr key={user.id} className="cursor-pointer transition hover:bg-slate-50" onClick={() => navigate(`/hotspot/users/${user.id}`)}>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-900">{user.username}</p>
                    <p className="text-xs text-slate-500">{user.customer_name ?? 'Voucher User'}</p>
                    {user.mac_address ? <p className="font-mono text-xs text-indigo-600">{user.mac_address}</p> : null}
                  </td>
                  <td className="px-4 py-3">
                    <p className="text-sm font-medium text-slate-800">{user.router_name ?? `Router #${user.router_id}`}</p>
                    <span className="mt-1 inline-block rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs text-indigo-700">{user.profile_name}</span>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-600">
                    {user.limit_uptime ? <div>Uptime: <span className="font-semibold">{user.limit_uptime}</span></div> : null}
                    {user.limit_bytes_total ? <div>Data: <span className="font-semibold">{(user.limit_bytes_total / 1048576).toFixed(0)} MB</span></div> : null}
                    {!user.limit_uptime && !user.limit_bytes_total ? <span className="text-slate-400">Unlimited</span> : null}
                  </td>
                  <td className="px-4 py-3">{getStatusBadge(user.status)}</td>
                  <td className="px-4 py-3">{getSyncBadge(user.sync_status)}</td>
                  <td className="px-4 py-3 text-right" onClick={(e) => e.stopPropagation()}>
                    <div className="flex items-center justify-end gap-1.5">
                      {canUpdate ? (
                        <Button size="sm" variant="secondary" onClick={() => onToggleStatus(user)}>
                          {user.status === 'active' ? 'Disable' : 'Enable'}
                        </Button>
                      ) : null}
                      {canManage && user.status === 'active' ? (
                        <Button size="sm" variant="secondary" className="text-amber-700 hover:bg-amber-50" onClick={() => onSuspend(user)}>
                          Suspend
                        </Button>
                      ) : null}
                      {canSync ? (
                        <Button size="sm" variant="secondary" onClick={() => onSync(user)} title="Audit and repair with router">
                          Sync
                        </Button>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
