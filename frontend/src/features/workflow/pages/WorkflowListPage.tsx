import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useWorkflowAction, useWorkflows } from '../api/useWorkflow';
import { WorkflowCard } from '../components/WorkflowCard';
import { WorkflowSkeleton } from '../components/WorkflowSkeleton';

export const WorkflowListPage = () => {
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const filters = useMemo(() => ({ search, status, page: 1, per_page: 50 }), [search, status]);
  const list = useWorkflows(filters);
  const action = useWorkflowAction();

  if (list.isLoading && !list.data) {
    return <WorkflowSkeleton />;
  }

  if (list.error) {
    return <Alert title="Unable to load workflows">{apiErrorMessage(list.error)}</Alert>;
  }

  const items = (list.data?.data ?? []).map((row) => row.attributes);

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Automation</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Workflows</h1>
          <p className="mt-1 text-sm text-slate-500">Create, enable, clone, test, and manage automation workflows.</p>
        </div>
        <Link
          to="/workflows/new"
          className="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
        >
          New Workflow
        </Link>
      </header>

      <div className="flex flex-wrap gap-3">
        <input
          className="min-w-[220px] flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
          placeholder="Search workflows…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <select
          className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">All statuses</option>
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="paused">Paused</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>

      {action.error ? <Alert title="Action failed">{apiErrorMessage(action.error)}</Alert> : null}

      <div className="grid gap-4 lg:grid-cols-2">
        {items.map((workflow) => (
          <WorkflowCard
            key={workflow.id}
            workflow={workflow}
            onAction={(act) => action.mutate({ id: workflow.id, action: act, payload: {} })}
          />
        ))}
      </div>

      {items.length === 0 ? (
        <div className="rounded-xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-slate-700">
          No workflows yet. Create your first automation workflow.
        </div>
      ) : null}
    </div>
  );
};
