import type { IntegrationDashboardData } from '../types';

interface Props {
  data: IntegrationDashboardData;
}

const Card = ({ label, value, sub }: { label: string; value: string | number; sub?: string }) => (
  <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
    <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</p>
    <p className="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{value}</p>
    {sub && <p className="mt-0.5 text-xs text-slate-400">{sub}</p>}
  </div>
);

export const IntegrationDashboardStats = ({ data }: Props) => {
  const { api_keys, webhooks, deliveries, events, connectors, request_stats } = data;

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <Card label="API Keys" value={api_keys.total} sub={`${api_keys.active} active`} />
      <Card label="Webhooks" value={webhooks.total} sub={`${webhooks.outbound} outbound · ${webhooks.inbound} inbound`} />
      <Card label="Failed Deliveries" value={deliveries.failed} sub={`${deliveries.pending_retries} pending retries`} />
      <Card label="Registered Events" value={events.total} sub={`${events.source_modules.length} source modules`} />
      <Card label="Connectors" value={connectors.total} sub={`${connectors.enabled} enabled`} />
      <Card label="Total Requests" value={request_stats.total_requests} />
      <Card label="Success Rate" value={
        request_stats.total_requests > 0
          ? `${Math.round((request_stats.success_count / request_stats.total_requests) * 100)}%`
          : '—'
      } />
      <Card label="Avg Duration" value={request_stats.avg_duration_ms !== null ? `${Math.round(request_stats.avg_duration_ms)}ms` : '—'} />
    </div>
  );
};
