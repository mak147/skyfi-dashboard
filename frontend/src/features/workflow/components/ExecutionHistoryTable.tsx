import type { WorkflowExecutionItem } from '../types';
import { WorkflowStatusBadge } from './WorkflowStatusBadge';

export const ExecutionHistoryTable = ({
  items,
  onRetry,
  onCancel,
}: {
  items: WorkflowExecutionItem[];
  onRetry?: (id: number) => void;
  onCancel?: (id: number) => void;
}) => {
  if (items.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
        No workflow executions found.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <table className="min-w-full text-left text-sm">
        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
          <tr>
            <th className="px-4 py-3">Workflow</th>
            <th className="px-4 py-3">Event</th>
            <th className="px-4 py-3">Source</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Duration</th>
            <th className="px-4 py-3">Attempts</th>
            <th className="px-4 py-3">When</th>
            <th className="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id} className="border-t border-slate-100 dark:border-slate-800">
              <td className="px-4 py-3 font-medium text-slate-800 dark:text-slate-100">
                {item.workflow_name || `#${item.workflow_id}`}
              </td>
              <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{item.trigger_event_key || '—'}</td>
              <td className="px-4 py-3 capitalize text-slate-600 dark:text-slate-300">{item.trigger_source}</td>
              <td className="px-4 py-3">
                <WorkflowStatusBadge status={item.status} />
              </td>
              <td className="px-4 py-3 text-slate-600 dark:text-slate-300">
                {item.duration_ms != null ? `${item.duration_ms} ms` : '—'}
              </td>
              <td className="px-4 py-3 text-slate-600 dark:text-slate-300">
                {item.attempt_number}/{item.max_attempts}
              </td>
              <td className="px-4 py-3 text-slate-600 dark:text-slate-300">
                {item.created_at ? new Date(item.created_at).toLocaleString() : '—'}
              </td>
              <td className="px-4 py-3">
                <div className="flex gap-2">
                  {onRetry && ['failed', 'partial', 'cancelled'].includes(item.status) ? (
                    <button type="button" className="text-xs font-semibold text-indigo-600" onClick={() => onRetry(item.id)}>
                      Retry
                    </button>
                  ) : null}
                  {onCancel && ['pending', 'scheduled', 'paused'].includes(item.status) ? (
                    <button type="button" className="text-xs font-semibold text-rose-600" onClick={() => onCancel(item.id)}>
                      Cancel
                    </button>
                  ) : null}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
