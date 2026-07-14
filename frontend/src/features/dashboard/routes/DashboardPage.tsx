import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getDashboard } from '../api/dashboardApi';
import { DashboardSkeleton } from '../components/DashboardSkeleton';
import { DashboardWidgetRenderer } from '../components/DashboardWidgetRenderer';

export const DashboardPage = () => {
  const dashboardQuery = useQuery({
    queryKey: ['dashboard'],
    queryFn: getDashboard,
    staleTime: 5 * 60 * 1000,
  });

  const generatedAt = useMemo(() => {
    if (!dashboardQuery.data?.generatedAt) {
      return null;
    }

    return new Intl.DateTimeFormat(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(dashboardQuery.data.generatedAt));
  }, [dashboardQuery.data?.generatedAt]);

  if (dashboardQuery.isLoading) {
    return <DashboardSkeleton />;
  }

  if (dashboardQuery.error) {
    return (
      <Alert title="Dashboard unavailable">
        {apiErrorMessage(dashboardQuery.error, 'Unable to load the dashboard. Please try again.')}
      </Alert>
    );
  }

  const dashboard = dashboardQuery.data;

  if (!dashboard) {
    return <Alert title="Dashboard unavailable">The dashboard response did not include any widgets.</Alert>;
  }

  return (
    <div className="space-y-6">
      <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div className="bg-gradient-to-br from-indigo-600 via-indigo-600 to-slate-900 px-6 py-8 text-white sm:px-8">
          <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div className="max-w-3xl">
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-100">Role-based analytics</p>
              <h1 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{dashboard.scope.title}</h1>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-indigo-50">{dashboard.scope.description}</p>
              <div className="mt-5 flex flex-wrap gap-2">
                {dashboard.roles.map((role) => (
                  <span key={role} className="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/20">
                    {role}
                  </span>
                ))}
              </div>
            </div>

            <div className="rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur">
              <p className="text-xs font-semibold uppercase tracking-wide text-indigo-100">Snapshot freshness</p>
              <p className="mt-2 text-sm font-semibold text-white">{generatedAt ?? 'Just now'}</p>
              <p className="mt-1 text-xs text-indigo-100">Cached for {Math.round(dashboard.cacheTtlSeconds / 60)} minutes by API contract.</p>
              <Button className="mt-4 bg-white text-indigo-700 hover:bg-indigo-50" size="sm" onClick={() => void dashboardQuery.refetch()} isLoading={dashboardQuery.isFetching}>
                Refresh snapshot
              </Button>
            </div>
          </div>
        </div>
      </section>

      <section aria-label="Dashboard widgets" className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {dashboard.widgets.map((widget) => (
          <DashboardWidgetRenderer key={widget.id} widget={widget} />
        ))}
      </section>
    </div>
  );
};
