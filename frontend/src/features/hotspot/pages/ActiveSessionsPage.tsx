import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useHotspotActiveSessions, disconnectActiveSession, forceLogoutUser } from '../api/useHotspot';
import { SessionTable } from '../components/SessionTable';

export const ActiveSessionsPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: sessions = [], isLoading, error, refetch } = useHotspotActiveSessions();

  const disconnectMutation = useMutation({
    mutationFn: ({ routerId, sessionId }: { routerId: number; sessionId: string }) =>
      disconnectActiveSession(routerId, sessionId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot', 'active-sessions'] }),
  });

  const forceLogoutMutation = useMutation({
    mutationFn: (username: string) => forceLogoutUser(username),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot', 'active-sessions'] }),
  });

  const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const uniqueRouters = new Set(sessions.map((s) => s.router_id)).size;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Active Hotspot Sessions</h1>
          <p className="mt-1 text-sm text-slate-500">
            Live monitoring of currently connected hotspot users across all routers. Auto-refreshes every 15s.
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => navigate('/hotspot')}>
            Back to Users
          </Button>
          <Button variant="secondary" onClick={() => refetch()}>
            Refresh Now
          </Button>
        </div>
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Online Users</p>
          <p className="mt-1 text-2xl font-bold text-emerald-700">{sessions.length}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Download</p>
          <p className="mt-1 text-2xl font-bold text-indigo-700">
            {formatBytes(sessions.reduce((sum, s) => sum + s.bytes_in, 0))}
          </p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Upload</p>
          <p className="mt-1 text-2xl font-bold text-amber-700">
            {formatBytes(sessions.reduce((sum, s) => sum + s.bytes_out, 0))}
          </p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Routers Active</p>
          <p className="mt-1 text-2xl font-bold text-slate-700">{uniqueRouters}</p>
        </div>
      </div>

      {error ? (
        <Alert title="Unable to load active sessions" variant="danger">
          {apiErrorMessage(error)}
        </Alert>
      ) : null}
      {disconnectMutation.error ? (
        <Alert title="Disconnect failed" variant="danger">
          {apiErrorMessage(disconnectMutation.error)}
        </Alert>
      ) : null}
      {forceLogoutMutation.error ? (
        <Alert title="Force logout failed" variant="danger">
          {apiErrorMessage(forceLogoutMutation.error)}
        </Alert>
      ) : null}

      <SessionTable
        sessions={sessions}
        isLoading={isLoading}
        canDisconnect={true}
        onDisconnect={(routerId, sessionId) =>
          disconnectMutation.mutate({ routerId, sessionId })
        }
        onForceLogout={(username) => forceLogoutMutation.mutate(username)}
      />
    </div>
  );
};
