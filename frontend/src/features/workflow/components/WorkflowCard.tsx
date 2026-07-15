import { Link } from 'react-router-dom';

import type { WorkflowItem } from '../types';
import { WorkflowStatusBadge } from './WorkflowStatusBadge';

export const WorkflowCard = ({
  workflow,
  onAction,
}: {
  workflow: WorkflowItem;
  onAction?: (action: 'enable' | 'disable' | 'pause' | 'resume' | 'clone' | 'run' | 'test') => void;
}) => (
  <article className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-900">
    <div className="flex items-start justify-between gap-3">
      <div>
        <Link to={`/workflows/${workflow.id}/edit`} className="text-lg font-semibold text-slate-900 hover:text-indigo-600 dark:text-white">
          {workflow.name}
        </Link>
        <p className="mt-1 text-sm text-slate-500">{workflow.description || 'No description'}</p>
      </div>
      <WorkflowStatusBadge status={workflow.status} />
    </div>

    <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
      <div>
        <dt className="text-xs uppercase tracking-wide text-slate-400">Trigger</dt>
        <dd className="mt-1 font-medium text-slate-700 dark:text-slate-200">{workflow.trigger_event_key || '—'}</dd>
      </div>
      <div>
        <dt className="text-xs uppercase tracking-wide text-slate-400">Schedule</dt>
        <dd className="mt-1 font-medium capitalize text-slate-700 dark:text-slate-200">{workflow.schedule_mode}</dd>
      </div>
      <div>
        <dt className="text-xs uppercase tracking-wide text-slate-400">Executions</dt>
        <dd className="mt-1 font-medium text-slate-700 dark:text-slate-200">{workflow.execution_count}</dd>
      </div>
      <div>
        <dt className="text-xs uppercase tracking-wide text-slate-400">Success / Fail</dt>
        <dd className="mt-1 font-medium text-slate-700 dark:text-slate-200">
          {workflow.success_count} / {workflow.failure_count}
        </dd>
      </div>
    </dl>

    {onAction ? (
      <div className="mt-4 flex flex-wrap gap-2">
        {workflow.is_enabled ? (
          <button type="button" className="rounded-lg border px-2.5 py-1 text-xs" onClick={() => onAction('disable')}>
            Disable
          </button>
        ) : (
          <button type="button" className="rounded-lg border px-2.5 py-1 text-xs" onClick={() => onAction('enable')}>
            Enable
          </button>
        )}
        <button type="button" className="rounded-lg border px-2.5 py-1 text-xs" onClick={() => onAction('run')}>
          Run
        </button>
        <button type="button" className="rounded-lg border px-2.5 py-1 text-xs" onClick={() => onAction('test')}>
          Test
        </button>
        <button type="button" className="rounded-lg border px-2.5 py-1 text-xs" onClick={() => onAction('clone')}>
          Clone
        </button>
      </div>
    ) : null}
  </article>
);
