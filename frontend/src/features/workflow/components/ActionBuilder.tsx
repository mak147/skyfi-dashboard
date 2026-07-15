import type { ActionCatalogItem, WorkflowActionDef } from '../types';

export const ActionBuilder = ({
  actions,
  catalog,
  onChange,
}: {
  actions: WorkflowActionDef[];
  catalog: ActionCatalogItem[];
  onChange: (next: WorkflowActionDef[]) => void;
}) => {
  const update = (index: number, patch: Partial<WorkflowActionDef>) => {
    const next = actions.map((action, i) => (i === index ? { ...action, ...patch } : action));
    onChange(next);
  };

  const remove = (index: number) => onChange(actions.filter((_, i) => i !== index));

  const add = (type: string) => {
    const meta = catalog.find((item) => item.type === type);
    onChange([
      ...actions,
      {
        type,
        name: meta?.label ?? type,
        config: {},
        order: actions.length + 1,
        continue_on_failure: false,
        is_enabled: true,
      },
    ]);
  };

  return (
    <div className="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
      <div>
        <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-100">Actions</h3>
        <p className="text-xs text-slate-500">Ordered steps executed by reusing existing module services.</p>
      </div>

      <div className="space-y-3">
        {actions.map((action, index) => {
          const meta = catalog.find((item) => item.type === action.type);
          return (
            <div key={`${action.type}-${index}`} className="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p className="font-medium text-slate-800 dark:text-slate-100">
                    {index + 1}. {action.name || action.type}
                  </p>
                  <p className="text-xs text-slate-500">{meta?.description || action.type}</p>
                </div>
                <div className="flex items-center gap-2">
                  <label className="flex items-center gap-1 text-xs text-slate-600 dark:text-slate-300">
                    <input
                      type="checkbox"
                      checked={Boolean(action.continue_on_failure)}
                      onChange={(e) => update(index, { continue_on_failure: e.target.checked })}
                    />
                    Continue on failure
                  </label>
                  <button type="button" className="text-xs text-rose-600" onClick={() => remove(index)}>
                    Remove
                  </button>
                </div>
              </div>
              <textarea
                className="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-xs dark:border-slate-600 dark:bg-slate-900"
                rows={4}
                value={JSON.stringify(action.config ?? {}, null, 2)}
                onChange={(e) => {
                  try {
                    const parsed = JSON.parse(e.target.value) as Record<string, unknown>;
                    update(index, { config: parsed });
                  } catch {
                    // keep typing until valid JSON
                  }
                }}
              />
            </div>
          );
        })}
      </div>

      <div className="flex flex-wrap gap-2">
        {catalog.map((item) => (
          <button
            key={item.type}
            type="button"
            className="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200"
            onClick={() => add(item.type)}
          >
            + {item.label}
          </button>
        ))}
      </div>
    </div>
  );
};
