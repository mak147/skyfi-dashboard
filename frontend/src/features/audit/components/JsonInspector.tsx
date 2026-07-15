import { useState } from 'react';

interface JsonInspectorProps {
  data: unknown;
  label?: string;
  defaultExpanded?: boolean;
}

export const JsonInspector = ({ data, label, defaultExpanded = false }: JsonInspectorProps) => {
  const [expanded, setExpanded] = useState(defaultExpanded);

  if (data === null || data === undefined) {
    return <span className="text-xs text-slate-400">—</span>;
  }

  const jsonString = JSON.stringify(data, null, 2);

  return (
    <div className="rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
      <button
        type="button"
        className="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300"
        onClick={() => setExpanded(!expanded)}
      >
        <span>{label ?? 'JSON Data'}</span>
        <span className="text-slate-400">{expanded ? '▲' : '▼'}</span>
      </button>
      {expanded && (
        <pre className="max-h-64 overflow-auto whitespace-pre-wrap px-3 pb-3 font-mono text-xs text-slate-700 dark:text-slate-300">
          {jsonString}
        </pre>
      )}
    </div>
  );
};
