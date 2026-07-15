import { Button } from '@/components/ui/button';

import { useRetryDelivery } from '../api/useIntegration';
import type { WebhookDeliveryItem, PageMeta } from '../types';

interface Props {
  items: WebhookDeliveryItem[];
  meta?: PageMeta;
  onPageChange?: (page: number) => void;
}

const statusBadge: Record<string, string> = {
  pending: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
  sent: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
  failed: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
  retrying: 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
};

export const DeliveryHistory = ({ items, meta, onPageChange }: Props) => {
  const retry = useRetryDelivery();

  return (
    <div className="space-y-4">
      <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 dark:bg-slate-800">
            <tr>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Event</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Status</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Attempt</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">HTTP Status</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Duration</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Error</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Created</th>
              <th className="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
            {items.map((d) => (
              <tr key={d.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td className="px-4 py-3 font-mono text-xs text-slate-900 dark:text-white">{d.event_key}</td>
                <td className="px-4 py-3">
                  <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${statusBadge[d.status] ?? 'bg-slate-100 text-slate-700'}`}>
                    {d.status}
                  </span>
                </td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{d.attempt_number}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{d.response_status_code ?? '—'}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{d.duration_ms !== null ? `${d.duration_ms}ms` : '—'}</td>
                <td className="max-w-[200px] truncate px-4 py-3 text-xs text-red-500">{d.error_message ?? '—'}</td>
                <td className="px-4 py-3 text-slate-500">{new Date(d.created_at).toLocaleString()}</td>
                <td className="px-4 py-3 text-right">
                  {(d.status === 'failed' || d.status === 'retrying') && (
                    <Button size="sm" variant="secondary" disabled={retry.isPending} onClick={() => retry.mutate(d.id)}>
                      Retry
                    </Button>
                  )}
                </td>
              </tr>
            ))}
            {items.length === 0 && (
              <tr><td colSpan={8} className="px-4 py-8 text-center text-slate-400">No deliveries yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>
      {meta && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-slate-500">{meta.total} deliveries</span>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => onPageChange?.(meta.current_page - 1)}>Prev</Button>
            <Button size="sm" variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange?.(meta.current_page + 1)}>Next</Button>
          </div>
        </div>
      )}
    </div>
  );
};
