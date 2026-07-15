export const AuditSkeleton = () => (
  <div className="space-y-6">
    <div className="flex gap-4">
      {Array.from({ length: 4 }).map((_, i) => (
        <div key={i} className="h-28 w-1/4 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
      ))}
    </div>
    <div className="h-16 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
    <div className="space-y-3">
      {Array.from({ length: 8 }).map((_, i) => (
        <div key={i} className="h-14 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
      ))}
    </div>
  </div>
);
