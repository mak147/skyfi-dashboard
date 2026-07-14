import { useParams, useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useHotspotUser, useHotspotUserStatistics, useHotspotSessionHistory } from '../api/useHotspot';
import { UsageStatistics } from '../components/UsageStatistics';

const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatUptime = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  if (hours > 0) return `${hours}h ${minutes}m`;
  return `${minutes}m`;
};

export const UserDetailsPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { can } = usePermissions();
  const userId = Number(id ?? '0');

  const { data: user, isLoading, error } = useHotspotUser(userId);
  const { data: stats, isLoading: statsLoading } = useHotspotUserStatistics(userId);
  const { data: history } = useHotspotSessionHistory(1, 10, userId);

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="h-8 w-64 animate-pulse rounded bg-slate-200" />
        <div className="h-64 animate-pulse rounded-xl bg-slate-100" />
      </div>
    );
  }

  if (error || !user) {
    return (
      <div className="space-y-6">
        <Alert title="Unable to load hotspot user" variant="danger">
          {error ? apiErrorMessage(error) : 'User not found.'}
        </Alert>
        <Button variant="secondary" onClick={() => navigate('/hotspot')}>
          Back to Users
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">{user.username}</h1>
          <p className="mt-1 text-sm text-slate-500">
            Hotspot user details, usage statistics, and session history.
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => navigate('/hotspot')}>
            Back to Users
          </Button>
          {can('hotspot.update') ? (
            <Button onClick={() => navigate(`/hotspot/users/${user.id}/edit`)}>Edit User</Button>
          ) : null}
        </div>
      </div>

      {/* Status Badges */}
      <div className="flex flex-wrap gap-2">
        <span
          className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${
            user.status === 'active'
              ? 'bg-emerald-50 text-emerald-700'
              : user.status === 'disabled'
              ? 'bg-slate-100 text-slate-600'
              : user.status === 'suspended'
              ? 'bg-amber-50 text-amber-700'
              : 'bg-indigo-50 text-indigo-700'
          }`}
        >
          {user.status}
        </span>
        <span
          className={`inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold ${
            user.sync_status === 'synced'
              ? 'bg-emerald-50 text-emerald-700'
              : 'bg-amber-50 text-amber-700'
          }`}
        >
          {user.sync_status === 'synced' ? '✓ Synced' : '⚠️ ' + user.sync_status.replaceAll('_', ' ')}
        </span>
      </div>

      {/* Info Grid */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Router</p>
          <p className="mt-1 text-sm font-semibold text-slate-800">
            {user.router_name ?? `Router #${user.router_id}`}
          </p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Profile</p>
          <p className="mt-1 text-sm font-semibold text-slate-800">{user.profile_name}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</p>
          <p className="mt-1 text-sm font-semibold text-slate-800">
            {user.customer_name ?? 'N/A (Voucher User)'}
          </p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">MAC Address</p>
          <p className="mt-1 font-mono text-sm font-semibold text-slate-800">
            {user.mac_address ?? 'Not bound'}
          </p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Uptime Limit</p>
          <p className="mt-1 text-sm font-semibold text-slate-800">{user.limit_uptime ?? 'Unlimited'}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Data Limit</p>
          <p className="mt-1 text-sm font-semibold text-slate-800">
            {user.limit_bytes_total ? formatBytes(user.limit_bytes_total) : 'Unlimited'}
          </p>
        </div>
      </div>

      {/* Usage Statistics — using the dedicated component */}
      <UsageStatistics stats={stats} isLoading={statsLoading} />

      {/* Session History */}
      {history && history.items.length > 0 ? (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h3 className="text-sm font-semibold text-slate-900 mb-3">Recent Sessions</h3>
          <div className="space-y-2">
            {history.items.map((s) => (
              <div key={s.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-3">
                <div>
                  <p className="text-sm font-medium text-slate-800">{s.username}</p>
                  <p className="text-xs text-slate-500">
                    {s.started_at} → {s.ended_at ?? 'Active'}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-sm font-semibold text-slate-800">{formatUptime(s.uptime_seconds)}</p>
                  <p className="text-xs text-slate-500">
                    ↓{formatBytes(s.bytes_in)} / ↑{formatBytes(s.bytes_out)}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      ) : null}

      {/* Notes */}
      {user.notes ? (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h3 className="text-sm font-semibold text-slate-900 mb-2">Notes</h3>
          <p className="text-sm text-slate-600">{user.notes}</p>
        </div>
      ) : null}
    </div>
  );
};
