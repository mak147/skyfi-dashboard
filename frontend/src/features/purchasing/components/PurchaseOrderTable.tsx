import { Link } from 'react-router-dom';
import type { PurchaseOrder } from '../types';
import { PurchasingStatusBadge } from './PurchasingStatusBadge';

export const PurchaseOrderTable = ({ items, isLoading }: { items: PurchaseOrder[]; isLoading: boolean }) => {
  if (isLoading) {
    return <div className="space-y-3">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="h-14 animate-pulse rounded-lg bg-slate-100" />)}</div>;
  }
  if (!items.length) {
    return <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"><p className="text-sm text-slate-500">No purchase orders found.</p></div>;
  }
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm">
        <thead className="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
          <tr>
            <th className="px-4 py-3">PO #</th>
            <th className="px-4 py-3">Supplier</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Total</th>
            <th className="px-4 py-3">Received</th>
            <th className="px-4 py-3">Order Date</th>
            <th className="px-4 py-3">Expected Delivery</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {items.map((o) => (
            <tr key={o.id} className="hover:bg-slate-50">
              <td className="px-4 py-3"><Link className="font-mono font-semibold text-indigo-600 hover:underline" to={`/purchasing/orders/${o.id}`}>{o.po_number}</Link></td>
              <td className="px-4 py-3 text-slate-700">{o.vendor_name}</td>
              <td className="px-4 py-3"><PurchasingStatusBadge status={o.status} /></td>
              <td className="px-4 py-3 font-semibold tabular-nums">PKR {Number(o.total_amount).toLocaleString()}</td>
              <td className="px-4 py-3 tabular-nums">{o.total_received ?? 0} / {o.total_ordered ?? 0}</td>
              <td className="px-4 py-3 text-slate-600">{o.order_date}</td>
              <td className="px-4 py-3 text-slate-600">{o.expected_delivery_date ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
