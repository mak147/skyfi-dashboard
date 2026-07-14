export const DashboardSkeleton = () => (
  <div className="space-y-6" aria-label="Loading dashboard">
    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
      <div className="h-4 w-40 animate-pulse rounded bg-slate-200" />
      <div className="mt-4 h-8 w-72 animate-pulse rounded bg-slate-200" />
      <div className="mt-3 h-4 w-full max-w-2xl animate-pulse rounded bg-slate-100" />
    </div>
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      {Array.from({ length: 4 }, (_, index) => (
        <div key={index} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
          <div className="h-4 w-28 animate-pulse rounded bg-slate-100" />
          <div className="mt-5 h-9 w-24 animate-pulse rounded bg-slate-200" />
          <div className="mt-4 h-3 w-36 animate-pulse rounded bg-slate-100" />
        </div>
      ))}
    </div>
    <div className="grid gap-4 xl:grid-cols-2">
      {Array.from({ length: 2 }, (_, index) => (
        <div key={index} className="h-72 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
          <div className="h-4 w-36 animate-pulse rounded bg-slate-100" />
          <div className="mt-8 h-44 animate-pulse rounded-xl bg-slate-100" />
        </div>
      ))}
    </div>
  </div>
);
