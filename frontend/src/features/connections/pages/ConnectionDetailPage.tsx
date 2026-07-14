import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Button } from '@/components/ui/button';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';
import { clsx } from 'clsx';

import { getConnection, activateConnection, suspendConnection, disconnectConnection } from '../api/connectionApi';
import { ConnectionStatusBadge } from '../components/ConnectionStatusBadge';
import { InstallationTimeline } from '../components/InstallationTimeline';

export const ConnectionDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const [activeTab, setActiveTab] = useState('overview');

  const { data: connection, isLoading, error } = useQuery({
    queryKey: ['connections', id],
    queryFn: () => getConnection(Number(id)),
  });

  const mutation = useMutation({
    mutationFn: async (action: 'activate' | 'suspend' | 'disconnect') => {
      if (action === 'activate') return activateConnection(Number(id));
      if (action === 'suspend') return suspendConnection(Number(id));
      if (action === 'disconnect') return disconnectConnection(Number(id));
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['connections', id] });
    },
  });

  if (isLoading) return <div className="animate-pulse space-y-4"><div className="h-8 bg-slate-200 rounded w-1/4"/><div className="h-64 bg-slate-100 rounded"/></div>;
  if (error || !connection) return <Alert title="Error" variant="danger">{apiErrorMessage(error, 'Connection not found.')}</Alert>;

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'network', label: 'Network' },
    { id: 'installation', label: 'Installation' },
    { id: 'billing', label: 'Billing' },
    { id: 'timeline', label: 'Activity' },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold tracking-tight text-slate-900">{connection.connection_number}</h1>
            <ConnectionStatusBadge status={connection.status} />
          </div>
          <p className="mt-1 text-sm text-slate-500">{connection.name}</p>
        </div>
        <div className="flex gap-2">
          {can('connections.update') && (
            <Button variant="secondary" onClick={() => navigate(`/connections/${id}/edit`)}>Edit</Button>
          )}
          {connection.status !== 'active' && can('connections.activate') && (
            <Button onClick={() => mutation.mutate('activate')} isLoading={mutation.isPending}>Activate</Button>
          )}
          {connection.status === 'active' && can('connections.suspend') && (
            <Button variant="secondary" onClick={() => mutation.mutate('suspend')} isLoading={mutation.isPending}>Suspend</Button>
          )}
        </div>
      </div>

      <div className="border-b border-slate-200">
        <nav className="-mb-px flex gap-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={clsx(
                'border-b-2 py-4 text-sm font-medium transition',
                activeTab === tab.id
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'
              )}
            >
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          {activeTab === 'overview' && (
            <div className="grid gap-6 sm:grid-cols-2">
              <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Customer Details</h3>
                <div className="mt-4 space-y-3">
                  <div>
                    <p className="text-xs text-slate-400">Name</p>
                    <p className="text-sm font-medium text-slate-900">{connection.customer_name}</p>
                  </div>
                  <Button variant="ghost" size="sm" className="w-full justify-start" onClick={() => navigate(`/customers/${connection.customer_id}`)}>
                    View Customer Profile
                  </Button>
                </div>
              </div>
              <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Service Plan</h3>
                <div className="mt-4 space-y-3">
                  <div>
                    <p className="text-xs text-slate-400">Package</p>
                    <p className="text-sm font-medium text-slate-900">{connection.package_name}</p>
                  </div>
                  <p className="text-sm text-slate-600 capitalize">Type: {connection.type.replace('_', ' ')}</p>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'network' && (
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Network Configuration</h3>
              <dl className="mt-4 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                  <dt className="text-xs text-slate-400">PPPoE Username</dt>
                  <dd className="mt-1 text-sm font-medium text-slate-900">{connection.pppoe_username || 'N/A'}</dd>
                </div>
                <div>
                  <dt className="text-xs text-slate-400">Static IP</dt>
                  <dd className="mt-1 text-sm font-medium text-slate-900">{connection.static_ip || 'N/A'}</dd>
                </div>
                <div>
                  <dt className="text-xs text-slate-400">MAC Address</dt>
                  <dd className="mt-1 text-sm font-medium text-slate-900">{connection.mac_address || 'N/A'}</dd>
                </div>
                <div>
                  <dt className="text-xs text-slate-400">VLAN ID</dt>
                  <dd className="mt-1 text-sm font-medium text-slate-900">{connection.vlan_id || 'N/A'}</dd>
                </div>
              </dl>
            </div>
          )}
          {activeTab === 'installation' && (
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-6">Installation Workflow</h3>
              <InstallationTimeline status={connection.status} createdAt={connection.created_at} />
            </div>
          )}
        </div>

        <div className="space-y-6">
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Status & Monitoring</h3>
            <div className="mt-4 space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-sm text-slate-500">Current Status</span>
                <ConnectionStatusBadge status={connection.status} />
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-slate-500">Last Online</span>
                <span className="text-sm font-medium text-slate-900">{connection.last_online_at ? new Date(connection.last_online_at).toLocaleString() : 'Never'}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
