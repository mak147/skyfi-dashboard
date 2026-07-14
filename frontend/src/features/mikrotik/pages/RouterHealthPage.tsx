import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { checkRouterHealth, getRouter, getRouterHealth } from '../api/mikrotikApi';
import { RouterStatistics } from '../components/RouterStatistics';
import { RouterStatusBadge } from '../components/RouterStatusBadge';

export const RouterHealthPage = () => {
  const { id } = useParams<{ id: string }>();
  const routerId = Number(id);
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const router = useQuery({ queryKey: ['mikrotik', 'router', id], queryFn: () => getRouter(routerId) });
  const health = useQuery({ queryKey: ['mikrotik', 'router', id, 'health'], queryFn: () => getRouterHealth(routerId) });
  const refresh = useMutation({ mutationFn: () => checkRouterHealth(routerId), onSuccess: (snapshot) => { queryClient.setQueryData(['mikrotik', 'router', id, 'health'], snapshot); queryClient.invalidateQueries({ queryKey: ['mikrotik', 'router', id] }); queryClient.invalidateQueries({ queryKey: ['mikrotik', 'routers'] }); } });
  if (router.isLoading) return <div className="h-64 animate-pulse rounded-xl bg-slate-100" />;
  if (router.error || !router.data) return <Alert title="Router unavailable" variant="danger">{apiErrorMessage(router.error, 'The requested router was not found.')}</Alert>;

  return <div className="space-y-6"><div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><Link className="text-sm font-semibold text-indigo-600" to={`/network/routers/${routerId}`}>← {router.data.name}</Link><div className="mt-3 flex items-center gap-3"><h1 className="text-2xl font-bold text-slate-900">Router Health</h1><RouterStatusBadge status={health.data?.status ?? router.data.last_connection_status} /></div><p className="mt-1 text-sm text-slate-500">Latest read-only RouterOS health snapshot.</p></div>{can('mikrotik.connect') ? <Button onClick={() => refresh.mutate()} isLoading={refresh.isPending} disabled={!router.data.is_enabled}>Run health check</Button> : null}</div>
    {health.error || refresh.error ? <Alert title="Health check unavailable" variant="danger">{apiErrorMessage(refresh.error ?? health.error)}</Alert> : null}
    <RouterStatistics health={health.data ?? null} />
    {health.data ? <section className="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm"><p><span className="font-semibold text-slate-800">Collected:</span> {new Date(health.data.checked_at).toLocaleString()}</p>{health.data.uptime ? <p className="mt-2"><span className="font-semibold text-slate-800">Router uptime:</span> {health.data.uptime}</p> : null}{health.data.error_message ? <p className="mt-2 text-red-700">{health.data.error_message}</p> : null}</section> : null}
  </div>;
};
