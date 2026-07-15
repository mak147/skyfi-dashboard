import clsx from 'clsx';

import type { ExecutionStatus, WorkflowStatus } from '../types';

const workflowTone: Record<WorkflowStatus, string> = {
  draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  paused: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  disabled: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
};

const executionTone: Record<ExecutionStatus, string> = {
  pending: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  scheduled: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
  running: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
  success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  failed: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
  partial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  skipped: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  cancelled: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
  paused: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
};

export const WorkflowStatusBadge = ({ status }: { status: WorkflowStatus | ExecutionStatus | string }) => {
  const tone =
    (workflowTone as Record<string, string>)[status] ??
    (executionTone as Record<string, string>)[status] ??
    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200';

  return (
    <span className={clsx('inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize', tone)}>
      {status.replaceAll('_', ' ')}
    </span>
  );
};
