import { useQuery } from '@tanstack/react-query';

import { getPackageStatistics } from '../api/packageApi';

const summaryCards = [
  { key: 'total', label: 'Total', className: 'text-indigo-700' },
  { key: 'active', label: 'Active', className: 'text-emerald-700' },
  { key: 'draft', label: 'Draft', className: 'text-amber-700' },
  { key: 'inactive', label: 'Inactive', className: 'text-slate-700' },
] as const;

export const PackageStatistics = () => {
  const query = useQuery({ queryKey: ['packages', 'statistics'], queryFn: getPackageStatistics });

  if (query.isLoading) {
    return <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">{[1, 2, 3, 4].map((item) => <div key={item} className="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />)}</div>;
  }
  if (!query.data) return null;

  const maximum = Math.max(...query.data.by_category.map((category) => category.count), 1);

  return (
    <div className="space-y-3">
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        {summaryCards.map((card) => (
          <article key={card.key} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p className="text-xs font-semibold uppercase text-slate-400">{card.label}</p>
            <p className={`mt-2 text-2xl font-bold ${card.className}`}>{query.data[card.key]}</p>
          </article>
        ))}
      </div>
      <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900" aria-label="Packages by category">
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-semibold text-slate-800 dark:text-slate-100">Catalog by category</h2>
          <span className="text-xs text-slate-400">Package count</span>
        </div>
        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {query.data.by_category.map((category) => (
            <div key={category.code}>
              <div className="flex justify-between text-xs"><span className="font-medium text-slate-600 dark:text-slate-300">{category.name}</span><span>{category.count}</span></div>
              <div className="mt-1 h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div className="h-2 rounded-full bg-indigo-500" style={{ width: `${(category.count / maximum) * 100}%` }} /></div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
};
