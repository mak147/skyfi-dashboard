import { useQuery } from '@tanstack/react-query';
import { getInvoiceActivities } from '../api/billingApi';
import type { InvoiceActivity } from '../types';

const actionLabel = (action: string): string => {
  const map: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    status_changed: 'Status Changed',
    deleted: 'Deleted',
    reminder_sent: 'Reminder Sent',
  };
  return map[action] || action;
};

const actionColor = (action: string): string => {
  const map: Record<string, string> = {
    created: 'bg-emerald-500',
    updated: 'bg-indigo-500',
    status_changed: 'bg-amber-500',
    deleted: 'bg-red-500',
    reminder_sent: 'bg-sky-500',
  };
  return map[action] || 'bg-slate-400';
};

export const InvoiceActivityTimeline = ({ invoiceId }: { invoiceId: number }) => {
  const { data, isLoading } = useQuery({
    queryKey: ['invoice-activities', invoiceId],
    queryFn: () => getInvoiceActivities(invoiceId),
    enabled: invoiceId > 0,
    staleTime: 30000,
  });

  if (isLoading) {
    return <div className="h-40 animate-pulse rounded-xl bg-slate-100" />;
  }

  if (!data || data.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 py-12 text-center">
        <p className="text-sm text-slate-500">No activity recorded yet.</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {data.map((activity: InvoiceActivity) => (
        <div key={activity.id} className="flex gap-4">
          <div className="flex flex-col items-center">
            <div className={`h-3 w-3 rounded-full ${actionColor(activity.action)}`} />
            <div className="mt-1 h-full w-px bg-slate-200" />
          </div>
          <div className="pb-6">
            <p className="text-sm font-semibold text-slate-900">
              {actionLabel(activity.action)}
              {activity.performed_by_name && (
                <span className="ml-1 font-normal text-slate-500">by {activity.performed_by_name}</span>
              )}
            </p>
            {activity.description && <p className="mt-1 text-sm text-slate-600">{activity.description}</p>}
            <p className="mt-1 text-xs text-slate-400">{new Date(activity.created_at).toLocaleString()}</p>
          </div>
        </div>
      ))}
    </div>
  );
};
