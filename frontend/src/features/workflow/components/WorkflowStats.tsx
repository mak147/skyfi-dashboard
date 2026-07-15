import type { WorkflowDashboardData } from '../types';

const StatCard = ({ label, value, hint }: { label: string; value: number | string; hint?: string }) => (
  <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
    <p className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{value}</p>
    {hint ? <p className="mt-1 text-xs text-slate-500">{hint}</p> : null}
  </div>
);

export const WorkflowStats = ({ data }: { data: WorkflowDashboardData }) => (
  <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <StatCard label="Active Workflows" value={data.workflows.active} hint={`${data.workflows.total} total`} />
    <StatCard label="Executions (24h)" value={data.last_24h.executions} hint={`${data.last_24h.in_flight} in flight`} />
    <StatCard label="Success (24h)" value={data.last_24h.success} hint={`${data.lifetime.success} lifetime`} />
    <StatCard label="Failed (24h)" value={data.last_24h.failed} hint={`${data.lifetime.failures} lifetime`} />
  </div>
);
