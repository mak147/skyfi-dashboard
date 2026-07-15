import { useMemo, useState } from 'react';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useExecutionAction, useWorkflowExecutions } from '../api/useWorkflow';
import { ExecutionHistoryTable } from '../components/ExecutionHistoryTable';
import { WorkflowSkeleton } from '../components/WorkflowSkeleton';

export const WorkflowHistoryPage = () => {
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const filters = useMemo(() => ({ search, status, page: 1, per_page: 50 }), [search, status]);
  const history = useWorkflowExecutions(filters);
  const action = useExecutionAction();

  if (history.isLoading && !history.data) {
    return <WorkflowSkeleton />;
  }

  if (history.error) {
    return <Alert title="Unable to load execution history">{apiErrorMessage(history.error)}</Alert>;
  }

  const items = (history.data?.data ?? []).map((row) => row.attributes);

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Automation</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Execution History</h1>
        <p className="mt-1 text-sm text-slate-500">
          Track execution time, status, duration, results, retries, and errors.
        </p>
      </header>

      <div className="flex flex-wrap gap-3">
        <input
          className="min-w-[220px] flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
          placeholder="Search executions…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <select
          className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">All statuses</option>
          {['pending', 'scheduled', 'running', 'success', 'failed', 'partial', 'skipped', 'cancelled', 'paused'].map(
            (s) => (
              <option key={s} value={s}>
                {s}
              </option>
            ),
          )}
        </select>
      </div>

      {action.error ? <Alert title="Action failed">{apiErrorMessage(action.error)}</Alert> : null}

      <ExecutionHistoryTable
        items={items}
        onRetry={(executionId) => action.mutate({ executionId, action: 'retry' })}
        onCancel={(executionId) => action.mutate({ executionId, action: 'cancel' })}
      />
    </div>
  );
};
