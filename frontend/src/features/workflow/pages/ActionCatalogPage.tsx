import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useActionCatalog } from '../api/useWorkflow';
import { WorkflowSkeleton } from '../components/WorkflowSkeleton';

export const ActionCatalogPage = () => {
  const catalog = useActionCatalog();

  if (catalog.isLoading && !catalog.data) {
    return <WorkflowSkeleton />;
  }

  if (catalog.error) {
    return <Alert title="Unable to load action catalog">{apiErrorMessage(catalog.error)}</Alert>;
  }

  const items = (catalog.data?.data ?? []).map((row) => row.attributes);

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Automation</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Action Catalog</h1>
        <p className="mt-1 text-sm text-slate-500">
          Supported workflow actions that call existing module services without duplicating business logic.
        </p>
      </header>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {items.map((item) => (
          <article
            key={item.type}
            className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"
          >
            <p className="text-xs font-semibold uppercase tracking-wide text-indigo-600">{item.module}</p>
            <h2 className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{item.label}</h2>
            <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{item.description}</p>
            <p className="mt-3 font-mono text-xs text-slate-500">{item.type}</p>
            <div className="mt-3 flex flex-wrap gap-1">
              {Object.keys(item.config_schema ?? {}).map((key) => (
                <span
                  key={key}
                  className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                >
                  {key}
                </span>
              ))}
            </div>
          </article>
        ))}
      </div>
    </div>
  );
};
