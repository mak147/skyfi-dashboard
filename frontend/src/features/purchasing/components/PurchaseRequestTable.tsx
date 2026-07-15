import { Link } from 'react-router-dom';
import type { PurchaseRequest } from '../types';
import { PurchasingStatusBadge, PriorityBadge } from './PurchasingStatusBadge';

export const PurchaseRequestTable = ({ items, isLoading }: { items: PurchaseRequest[]; isLoading: boolean }) => {
  if (isLoading) {
    return <div className="space-y-3">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="h-14 animate-pulse rounded-lg bg-slate-100" />)}</div>;
  }
  if (!items.length) {
    return <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"><p className="text-sm text-slate-500">No purchase requests found.</p></div>;
  }
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm">
        <thead className="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
          <tr>
            <th className="px-4 py-3">Request #</th>
            <th className="px-4 py-3">Requester</th>
            <th className="px-4 py-3">Priority</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Items</th>
            <th className="px-4 py-3">Required Date</th>
            <th className="px-4 py-3">Created</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {items.map((r) => (
            <tr key={r.id} className="hover:bg-slate-50">
              <td className="px-4 py-3"><Link className="font-mono font-semibold text-indigo-600 hover:underline" to={`/purchasing/requests/${r.id}`}>{r.request_number}</Link></td>
              <td className="px-4 py-3 text-slate-700">{r.requester_name}</td>
              <td className="px-4 py-3"><PriorityBadge priority={r.priority} /></td>
              <td className="px-4 py-3"><PurchasingStatusBadge status={r.status} /></td>
              <td className="px-4 py-3 tabular-nums">{r.item_count}</td>
              <td className="px-4 py-3 text-slate-600">{r.required_date ?? '—'}</td>
              <td className="px-4 py-3 text-xs text-slate-400">{new Date(r.created_at).toLocaleDateString()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
