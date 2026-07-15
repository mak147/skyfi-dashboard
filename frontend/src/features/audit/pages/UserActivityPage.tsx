import { useState } from 'react';
import { useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useUserActivity } from '../api/useAudit';
import { ActivityTimeline } from '../components/ActivityTimeline';
import { AuditSkeleton } from '../components/AuditSkeleton';

export const UserActivityPage = () => {
  const { id } = useParams<{ id: string }>();
  const userId = parseInt(id ?? '0', 10);
  const [page] = useState(1);
  const activity = useUserActivity(userId, { page, per_page: 50 });

  if (activity.isLoading && !activity.data) {
    return <AuditSkeleton />;
  }

  if (activity.error) {
    return <Alert title="Activity unavailable">{apiErrorMessage(activity.error)}</Alert>;
  }

  const items = activity.data?.data.map((r) => r.attributes) ?? [];
  const meta = activity.data?.meta;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Audit & Compliance</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">User Activity</h1>
        <p className="mt-1 text-sm text-slate-500">
          Activity timeline for user #{userId}
        </p>
      </header>

      <ActivityTimeline items={items} isLoading={activity.isLoading} />

      {meta && (
        <div className="text-sm text-slate-500">
          Showing {items.length} of {meta.total} events
        </div>
      )}
    </div>
  );
};
