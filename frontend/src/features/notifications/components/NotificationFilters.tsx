import type { NotificationFilters as Filters } from '../types';

export const NotificationFiltersBar = ({
  filters,
  categories = [],
  onChange,
}: {
  filters: Filters;
  categories?: string[];
  onChange: (next: Filters) => void;
}) => (
  <section className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-700 dark:bg-slate-900">
    <input
      className="h-10 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
      placeholder="Search notifications…"
      value={filters.search ?? ''}
      onChange={(e) => onChange({ ...filters, search: e.target.value, page: 1 })}
    />
    <select
      className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
      value={filters.status ?? ''}
      onChange={(e) => onChange({ ...filters, status: e.target.value, page: 1 })}
    >
      <option value="">All statuses</option>
      {['unread', 'read', 'archived'].map((s) => (
        <option key={s} value={s}>
          {s}
        </option>
      ))}
    </select>
    <select
      className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
      value={filters.category ?? ''}
      onChange={(e) => onChange({ ...filters, category: e.target.value, page: 1 })}
    >
      <option value="">All categories</option>
      {categories.map((c) => (
        <option key={c} value={c}>
          {c}
        </option>
      ))}
    </select>
    <select
      className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
      value={filters.severity ?? ''}
      onChange={(e) => onChange({ ...filters, severity: e.target.value, page: 1 })}
    >
      <option value="">All severities</option>
      {['info', 'success', 'warning', 'critical'].map((s) => (
        <option key={s} value={s}>
          {s}
        </option>
      ))}
    </select>
  </section>
);
