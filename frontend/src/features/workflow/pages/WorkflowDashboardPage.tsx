import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useWorkflowDashboard } from '../api/useWorkflow';
import { ExecutionHistoryTable } from '../components/ExecutionHistoryTable';
import { WorkflowSkeleton } from '../components/WorkflowSkeleton';
import { WorkflowStats } from '../components/WorkflowStats';

export const WorkflowDashboardPage = () => {
  const dashboard = useWorkflowDashboard();

  if (dashboard.isLoading && !dashboard.data) {
    return <WorkflowSkeleton />;
  }

  if (dashboard.error) {
    return <Alert title="Workflow dashboard unavailable">{apiErrorMessage(dashboard.error)}</Alert>;
  }

  const data = dashboard.data!;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Automation</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Workflow Automation Engine</h1>
          <p className="mt-1 text-sm text-slate-500">
            Orchestrate configurable business workflows across billing, network, support, and operations.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link
            to="/workflows/list"
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-600"
          >
            All Workflows
          </Link>
          <Link
            to="/workflows/new"
            className="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          >
            Create Workflow
          </Link>
        </div>
      </header>

      <WorkflowStats data={data} />

      <section className="space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold">Recent Executions</h2>
          <Link to="/workflows/history" className="text-sm font-semibold text-indigo-600">
            View history
          </Link>
        </div>
        <ExecutionHistoryTable items={data.recent_executions ?? []} />
      </section>
    </div>
  );
};
