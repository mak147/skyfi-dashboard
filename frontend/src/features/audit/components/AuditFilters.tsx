import type { AuditFilterOptions, AuditLogFilters } from '../types';

interface AuditFiltersBarProps {
  filters: AuditLogFilters;
  options?: AuditFilterOptions;
  onChange: (filters: AuditLogFilters) => void;
}

export const AuditFiltersBar = ({ filters, options, onChange }: AuditFiltersBarProps) => {
  const update = (patch: Partial<AuditLogFilters>) => onChange({ ...filters, ...patch, page: 1 });

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
      <div className="flex flex-wrap gap-3">
        <input
          type="text"
          placeholder="Search actions, modules…"
          value={filters.search ?? ''}
          onChange={(e) => update({ search: e.target.value || undefined })}
          className="min-w-[200px] rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        />

        <select
          value={filters.module ?? ''}
          onChange={(e) => update({ module: e.target.value || undefined })}
          className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Modules</option>
          {(options?.modules ?? []).map((m) => (
            <option key={m} value={m}>{m}</option>
          ))}
        </select>

        <select
          value={filters.severity ?? ''}
          onChange={(e) => update({ severity: e.target.value || undefined })}
          className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Severities</option>
          {(options?.severities ?? ['info', 'warning', 'critical']).map((s) => (
            <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
          ))}
        </select>

        <select
          value={filters.entity_type ?? ''}
          onChange={(e) => update({ entity_type: e.target.value || undefined })}
          className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Entity Types</option>
          {(options?.entity_types ?? []).map((t) => (
            <option key={t} value={t}>{t}</option>
          ))}
        </select>

        <input
          type="date"
          value={filters.date_from ?? ''}
          onChange={(e) => update({ date_from: e.target.value || undefined })}
          className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          title="From date"
        />

        <input
          type="date"
          value={filters.date_to ?? ''}
          onChange={(e) => update({ date_to: e.target.value || undefined })}
          className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          title="To date"
        />

        <button
          type="button"
          onClick={() => onChange({ page: 1, per_page: 25 })}
          className="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          Reset
        </button>
      </div>
    </div>
  );
};
