import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import type { PppoeAccount } from '../types';

interface PPPoETableProps {
  accounts: PppoeAccount[];
  isLoading: boolean;
  canUpdate: boolean;
  canManage: boolean;
  canSync: boolean;
  onToggleStatus: (account: PppoeAccount) => void;
  onReconnect: (account: PppoeAccount) => void;
  onSync: (account: PppoeAccount) => void;
}

export const PPPoETable = ({
  accounts,
  isLoading,
  canUpdate,
  canManage,
  canSync,
  onToggleStatus,
  onReconnect,
  onSync,
}: PPPoETableProps) => {
  const navigate = useNavigate();

  const getStatusBadge = (status: PppoeAccount['status']) => {
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

  const getSyncBadge = (syncStatus: PppoeAccount['sync_status']) => {
    switch (syncStatus) {
      case 'synced':
        return <span className="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-800">✓ Synced</span>;
      case 'missing_on_router':
        return <span className="inline-flex items-center gap-1 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-800">⚠️ Missing on Router</span>;
      case 'conflict':
        return <span className="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800">⚡ Mismatch</span>;
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
              <th className="px-4 py-3">Network Bindings</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Sync State</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {isLoading ? (
              Array.from({ length: 5 }).map((_, index) => (
                <tr key={index}>
                  <td colSpan={6} className="px-4 py-5">
                    <div className="h-5 animate-pulse rounded bg-slate-100" />
                  </td>
                </tr>
              ))
            ) : null}
            {!isLoading && accounts.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-sm text-slate-500">
                  No PPPoE accounts match the current filters.
                </td>
              </tr>
            ) : null}
            {!isLoading &&
              accounts.map((account) => (
                <tr
                  key={account.id}
                  className="cursor-pointer transition hover:bg-slate-50"
                  onClick={() => navigate(`/network/pppoe/accounts/${account.id}`)}
                >
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-900">{account.username}</p>
                    <p className="text-xs text-slate-500">{account.customer_name ?? `Customer #${account.customer_id}`}</p>
                    {account.connection_number ? <p className="font-mono text-xs text-indigo-600">{account.connection_number}</p> : null}
                  </td>
                  <td className="px-4 py-3">
                    <p className="text-sm font-medium text-slate-800">{account.router_name ?? `Router #${account.router_id}`}</p>
                    <span className="mt-1 inline-block rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs text-indigo-700">
                      {account.profile}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-600">
                    <div>IP: <span className="font-mono">{account.static_ip ?? 'Pool assigned'}</span></div>
                    {account.mac_binding ? <div>MAC: <span className="font-mono">{account.mac_binding}</span></div> : null}
                    {account.rate_limit ? <div>Rate: <span className="font-semibold">{account.rate_limit}</span></div> : null}
                  </td>
                  <td className="px-4 py-3">{getStatusBadge(account.status)}</td>
                  <td className="px-4 py-3">{getSyncBadge(account.sync_status)}</td>
                  <td className="px-4 py-3 text-right" onClick={(event) => event.stopPropagation()}>
                    <div className="flex items-center justify-end gap-1.5">
                      {canUpdate ? (
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => onToggleStatus(account)}
                        >
                          {account.status === 'active' ? 'Disable' : 'Enable'}
                        </Button>
                      ) : null}
                      {canManage && account.status === 'active' ? (
                        <Button
                          size="sm"
                          variant="secondary"
                          className="text-amber-700 hover:bg-amber-50"
                          onClick={() => onReconnect(account)}
                        >
                          Reconnect
                        </Button>
                      ) : null}
                      {canSync ? (
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => onSync(account)}
                          title="Audit and repair with router"
                        >
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
