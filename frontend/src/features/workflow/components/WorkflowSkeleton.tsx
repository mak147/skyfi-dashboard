export const WorkflowSkeleton = () => (
  <div className="space-y-4">
    <div className="h-10 w-64 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-800" />
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      {Array.from({ length: 4 }).map((_, i) => (
        <div key={i} className="h-28 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
      ))}
    </div>
    <div className="h-72 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
  </div>
);
