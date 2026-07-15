import { Button } from '@/components/ui/button';

import type { WebhookItem, PageMeta } from '../types';

interface Props {
  items: WebhookItem[];
  meta?: PageMeta;
  onPageChange?: (page: number) => void;
  onEdit?: (id: number) => void;
  onDelete?: (id: number) => void;
}

export const WebhookTable = ({ items, meta, onPageChange, onEdit, onDelete }: Props) => (
  <div className="space-y-4">
    <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
      <table className="min-w-full text-sm">
        <thead className="bg-slate-50 dark:bg-slate-800">
          <tr>
            <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Name</th>
            <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">URL</th>
            <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Direction</th>
            <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Events</th>
            <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Status</th>
            <th className="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
          {items.map((wh) => (
            <tr key={wh.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
              <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">{wh.name}</td>
              <td className="max-w-[200px] truncate px-4 py-3 text-slate-500">{wh.url}</td>
              <td className="px-4 py-3">
                <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${wh.is_inbound ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300'}`}>
                  {wh.is_inbound ? 'Inbound' : 'Outbound'}
                </span>
              </td>
              <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                <div className="flex flex-wrap gap-1">
                  {wh.events.slice(0, 2).map((e) => (
                    <span key={e} className="inline-block rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-400">{e}</span>
                  ))}
                  {wh.events.length > 2 && <span className="text-xs text-slate-400">+{wh.events.length - 2}</span>}
                </div>
              </td>
              <td className="px-4 py-3">
                <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${wh.is_active ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'}`}>
                  {wh.is_active ? 'Active' : 'Inactive'}
                </span>
              </td>
              <td className="px-4 py-3 text-right space-x-2">
                {onEdit && <Button size="sm" variant="secondary" onClick={() => onEdit(wh.id)}>Edit</Button>}
                {onDelete && <Button size="sm" variant="secondary" onClick={() => onDelete(wh.id)}>Delete</Button>}
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-400">No webhooks configured.</td></tr>
          )}
        </tbody>
      </table>
    </div>
    {meta && (
      <div className="flex items-center justify-between text-sm">
        <span className="text-slate-500">{meta.total} webhooks</span>
        <div className="flex gap-2">
          <Button size="sm" variant="secondary" disabled={meta.current_page <= 1} onClick={() => onPageChange?.(meta.current_page - 1)}>Prev</Button>
          <Button size="sm" variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange?.(meta.current_page + 1)}>Next</Button>
        </div>
      </div>
    )}
  </div>
);
