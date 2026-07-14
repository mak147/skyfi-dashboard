import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { disconnectActiveSession, useActiveSessions } from '../api/usePppoe';
import { ActiveSessionTable } from '../components/ActiveSessionTable';
import { RouterSelector } from '../components/RouterSelector';
import type { PppoeActiveSession } from '../types';

export const ActiveSessionsPage = () => {
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const [selectedRouterId, setSelectedRouterId] = useState<number | ''>('');
  const [searchQuery, setSearchQuery] = useState('');

  const { data: sessions = [], isLoading, error } = useActiveSessions(
    selectedRouterId ? Number(selectedRouterId) : undefined
  );

  const disconnectMutation = useMutation({
    mutationFn: ({ routerId, sessionId }: { routerId: number; sessionId: string }) =>
      disconnectActiveSession(routerId, sessionId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pppoe', 'active-sessions'] }),
  });

  const filteredSessions = sessions.filter((s) => {
    if (!searchQuery) return true;
    const q = searchQuery.toLowerCase();
    return (
      s.username.toLowerCase().includes(q) ||
      (s.ip_address && s.ip_address.toLowerCase().includes(q)) ||
      (s.caller_id && s.caller_id.toLowerCase().includes(q))
    );
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Live PPPoE Active Sessions</h1>
          <p className="mt-1 text-sm text-slate-500">
            Real-time subscriber dial-in monitoring directly from MikroTik RouterOS <code className="text-indigo-600">/ppp/active/print</code> across your network.
          </p>
        </div>
        <Button variant="secondary" onClick={() => queryClient.invalidateQueries({ queryKey: ['pppoe', 'active-sessions'] })}>
          Refresh Now
        </Button>
      </div>

      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="w-full sm:w-72">
          <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Filter by Router</label>
          <RouterSelector value={selectedRouterId} onChange={setSelectedRouterId} />
        </div>
        <div className="w-full sm:w-72">
          <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Search active session</label>
          <input
            type="text"
            placeholder="Search username, IP, MAC..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
      </div>

      {error ? (
        <Alert title="Error polling live sessions" variant="danger">
          {apiErrorMessage(error)}
        </Alert>
      ) : null}

      {disconnectMutation.error ? (
        <Alert title="Failed to disconnect user" variant="danger">
          {apiErrorMessage(disconnectMutation.error)}
        </Alert>
      ) : null}

      <ActiveSessionTable
        sessions={filteredSessions}
        isLoading={isLoading}
        canManage={can('pppoe.manage')}
        onDisconnect={(session: PppoeActiveSession) => {
          if (confirm(`Are you sure you want to force disconnect ${session.username} (${session.ip_address})?`)) {
            disconnectMutation.mutate({ routerId: session.router_id, sessionId: session.id || session.username });
          }
        }}
      />
    </div>
  );
};
