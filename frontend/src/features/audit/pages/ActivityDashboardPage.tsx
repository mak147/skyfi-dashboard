import { useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useActivity, useAuditDashboard } from '../api/useAudit';
import { ActivityTimeline } from '../components/ActivityTimeline';
import { AuditSkeleton } from '../components/AuditSkeleton';

export const ActivityDashboardPage = () => {
  const { can } = usePermissions();
  const dashboard = useAuditDashboard();
  const [activityPage] = useState(1);
  const activity = useActivity({ page: activityPage, per_page: 20 });

  if (dashboard.isLoading && !dashboard.data) {
    return <AuditSkeleton />;
  }

  if (dashboard.error) {
    return <Alert title="Dashboard unavailable">{apiErrorMessage(dashboard.error)}</Alert>;
  }

  const stats = dashboard.data;
  const activityItems = activity.data?.data.map((r) => r.attributes) ?? [];

  const statCards = [
    { label: 'Total Logs', value: stats?.total_logs ?? 0, color: 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' },
    { label: 'Today', value: stats?.today_count ?? 0, color: 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' },
    { label: 'This Week', value: stats?.week_count ?? 0, color: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' },
    { label: 'Critical', value: stats?.critical_count ?? 0, color: 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' },
  ];

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Audit & Compliance</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Activity Dashboard</h1>
          <p className="mt-1 text-sm text-slate-500">
            Centralized audit trail, activity monitoring, and compliance management.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to="/audit/logs">
            <Button variant="secondary">Audit Logs</Button>
          </Link>
          {can('audit.export') && (
            <Link to="/audit/exports">
              <Button variant="secondary">Exports</Button>
            </Link>
          )}
          {can('compliance.manage') && (
            <Link to="/audit/compliance">
              <Button variant="secondary">Compliance</Button>
            </Link>
          )}
        </div>
      </header>

      {/* Stat Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {statCards.map((card) => (
          <div key={card.label} className={`rounded-xl border border-slate-200 p-5 dark:border-slate-700 ${card.color}`}>
            <p className="text-xs font-semibold uppercase tracking-wide opacity-75">{card.label}</p>
            <p className="mt-2 text-3xl font-bold">{card.value.toLocaleString()}</p>
          </div>
        ))}
      </div>

      {/* By Module */}
      {stats?.by_module && stats.by_module.length > 0 && (
        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
          <h2 className="text-sm font-bold text-slate-900 dark:text-white">Logs by Module</h2>
          <div className="mt-3 space-y-2">
            {stats.by_module.map((m) => (
              <div key={m.module} className="flex items-center gap-3">
                <span className="w-24 truncate text-xs font-semibold text-slate-600 dark:text-slate-300">{m.module}</span>
                <div className="h-2 flex-1 rounded-full bg-slate-100 dark:bg-slate-800">
                  <div
                    className="h-2 rounded-full bg-indigo-500"
                    style={{ width: `${Math.min(100, (m.count / Math.max(1, stats.total_logs)) * 100)}%` }}
                  />
                </div>
                <span className="text-xs text-slate-500">{m.count}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Recent Activity Timeline */}
      <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
        <h2 className="text-sm font-bold text-slate-900 dark:text-white">Recent Activity</h2>
        <div className="mt-4">
          <ActivityTimeline items={activityItems} isLoading={activity.isLoading} />
        </div>
      </div>
    </div>
  );
};
