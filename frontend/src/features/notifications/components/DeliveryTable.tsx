import { DeliveryStatusBadge } from './DeliveryStatus';
import type { DeliveryRecord } from '../types';

export const DeliveryTable = ({
  deliveries,
  isLoading,
}: {
  deliveries: DeliveryRecord[];
  isLoading?: boolean;
}) => {
  if (isLoading) {
    return <div className="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }

  if (deliveries.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-slate-700">
        No delivery history yet.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
      <table className="min-w-full text-left text-sm">
        <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-800">
          <tr>
            <th className="px-4 py-3">ID</th>
            <th className="px-4 py-3">Channel</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Provider</th>
            <th className="px-4 py-3">Subject</th>
            <th className="px-4 py-3">Recipient</th>
            <th className="px-4 py-3">Created</th>
          </tr>
        </thead>
        <tbody>
          {deliveries.map((d) => (
            <tr key={d.id} className="border-b border-slate-100 align-top dark:border-slate-800">
              <td className="px-4 py-3 font-mono text-xs">{d.id}</td>
              <td className="px-4 py-3">{d.channel}</td>
              <td className="px-4 py-3">
                <DeliveryStatusBadge status={d.status} />
                {d.fail_reason ? <p className="mt-1 max-w-xs text-xs text-rose-500">{d.fail_reason}</p> : null}
              </td>
              <td className="px-4 py-3 text-xs text-slate-500">{d.provider ?? '—'}</td>
              <td className="px-4 py-3">
                <p className="font-medium">{d.subject ?? '—'}</p>
                <p className="mt-1 max-w-sm truncate text-xs text-slate-400">{d.body}</p>
              </td>
              <td className="px-4 py-3">{d.recipient_user_id ?? '—'}</td>
              <td className="px-4 py-3 text-xs text-slate-500">{new Date(d.created_at).toLocaleString()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
