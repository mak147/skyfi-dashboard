interface DiffViewerProps {
  oldValues?: Record<string, unknown> | null;
  newValues?: Record<string, unknown> | null;
}

export const DiffViewer = ({ oldValues, newValues }: DiffViewerProps) => {
  const oldObj = oldValues ?? {};
  const newObj = newValues ?? {};
  const allKeys = Array.from(new Set([...Object.keys(oldObj), ...Object.keys(newObj)])).sort();

  if (allKeys.length === 0) {
    return <p className="text-sm text-slate-400">No data changes recorded.</p>;
  }

  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
      <table className="w-full text-sm">
        <thead>
          <tr className="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
            <th className="px-3 py-2 text-left">Field</th>
            <th className="px-3 py-2 text-left">Before</th>
            <th className="px-3 py-2 text-left">After</th>
          </tr>
        </thead>
        <tbody>
          {allKeys.map((key) => {
            const oldVal = oldObj[key];
            const newVal = newObj[key];
            const changed = JSON.stringify(oldVal) !== JSON.stringify(newVal);

            return (
              <tr key={key} className={changed ? 'bg-amber-50 dark:bg-amber-900/10' : ''}>
                <td className="whitespace-nowrap px-3 py-2 font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">{key}</td>
                <td className="px-3 py-2 font-mono text-xs text-slate-600 dark:text-slate-400">
                  {oldVal === undefined ? <span className="text-slate-300">—</span> : formatValue(oldVal)}
                </td>
                <td className="px-3 py-2 font-mono text-xs">
                  {newVal === undefined ? <span className="text-slate-300">—</span> : (
                    <span className={changed ? 'font-semibold text-green-700 dark:text-green-400' : 'text-slate-600 dark:text-slate-400'}>
                      {formatValue(newVal)}
                    </span>
                  )}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};

const formatValue = (val: unknown): string => {
  if (val === null) return 'null';
  if (typeof val === 'boolean') return val ? 'true' : 'false';
  if (typeof val === 'object') return JSON.stringify(val);
  return String(val);
};
