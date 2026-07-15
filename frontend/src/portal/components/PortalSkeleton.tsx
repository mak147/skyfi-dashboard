export const PortalSkeleton = () => (
  <div className="space-y-6">
    <div className="h-32 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800" />
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {Array.from({ length: 4 }).map((_, i) => (
        <div key={i} className="h-28 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
      ))}
    </div>
    <div className="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
  </div>
);

export const CardSkeleton = ({ rows = 5 }: { rows?: number }) => (
  <div className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-700 dark:bg-slate-900">
    <div className="h-6 w-1/3 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
    {Array.from({ length: rows }).map((_, i) => (
      <div key={i} className="h-10 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
    ))}
  </div>
);
