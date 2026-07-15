import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useTriggerCatalog } from '../api/useWorkflow';
import { WorkflowSkeleton } from '../components/WorkflowSkeleton';

export const TriggerCatalogPage = () => {
  const catalog = useTriggerCatalog();

  if (catalog.isLoading && !catalog.data) {
    return <WorkflowSkeleton />;
  }

  if (catalog.error) {
    return <Alert title="Unable to load trigger catalog">{apiErrorMessage(catalog.error)}</Alert>;
  }

  const items = (catalog.data?.data ?? []).map((row) => row.attributes);

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Automation</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Trigger Catalog</h1>
        <p className="mt-1 text-sm text-slate-500">
          Event-based triggers sourced from the platform event registry across all modules.
        </p>
      </header>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
            <tr>
              <th className="px-4 py-3">Event Key</th>
              <th className="px-4 py-3">Module</th>
              <th className="px-4 py-3">Description</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <tr key={item.event_key} className="border-t border-slate-100 dark:border-slate-800">
                <td className="px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                  {item.event_key}
                </td>
                <td className="px-4 py-3 capitalize text-slate-700 dark:text-slate-200">{item.source_module}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{item.description || '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
