import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePurchasingDashboard } from '../api/usePurchasing';
import { ProcurementStatistics } from '../components/ProcurementStatistics';
import { PurchasingStatusBadge } from '../components/PurchasingStatusBadge';

export const PurchasingDashboardPage = () => {
  const q = usePurchasingDashboard();

  if (q.isLoading) {
    return (
      <div className="space-y-5">
        <div className="h-20 animate-pulse rounded-xl bg-slate-200" />
        <div className="grid gap-4 md:grid-cols-5">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="h-28 animate-pulse rounded-xl bg-slate-200" />)}</div>
      </div>
    );
  }

  if (q.error || !q.data) {
    return <Alert title="Dashboard unavailable">{apiErrorMessage(q.error)}</Alert>;
  }

  const data = q.data;
  const maxSpend = Math.max(...data.monthly_spend.map((m) => m.amount), 1);

  return (
    <div className="space-y-6">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Purchasing &amp; Procurement</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900">Procurement Dashboard</h1>
          <p className="mt-1 text-sm text-slate-500">Purchase lifecycle, approvals, deliveries, and spend tracking.</p>
        </div>
        <div className="flex flex-wrap gap-2 text-sm font-semibold">
          <Link className="rounded-md border bg-white px-3 py-2 text-slate-700 hover:bg-slate-50" to="/purchasing/requests">Requests</Link>
          <Link className="rounded-md border bg-white px-3 py-2 text-slate-700 hover:bg-slate-50" to="/purchasing/orders">Orders</Link>
          <Link className="rounded-md border bg-white px-3 py-2 text-slate-700 hover:bg-slate-50" to="/purchasing/receipts">Receipts</Link>
          <Link className="rounded-md bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700" to="/purchasing/invoices">Invoices</Link>
        </div>
      </header>

      <ProcurementStatistics data={data} />

      <div className="grid gap-6 xl:grid-cols-2">
        {/* PO Status Breakdown */}
        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="font-semibold text-slate-900">Purchase Orders by Status</h2>
          <div className="mt-4 space-y-3">
            {data.po_by_status.length ? data.po_by_status.map((s) => (
              <div key={s.status} className="flex items-center justify-between">
                <PurchasingStatusBadge status={s.status} />
                <span className="font-semibold tabular-nums">{s.total}</span>
              </div>
            )) : <p className="text-sm text-slate-400">No orders yet.</p>}
          </div>
        </section>

        {/* Monthly Spend */}
        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="font-semibold text-slate-900">Monthly Procurement Spend</h2>
          <div className="mt-4 space-y-3">
            {data.monthly_spend.map((m) => (
              <div key={m.month}>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-600">{m.month}</span>
                  <span className="font-semibold tabular-nums">PKR {m.amount.toLocaleString()}</span>
                </div>
                <div className="mt-1 h-2 rounded-full bg-slate-100">
                  <div className="h-2 rounded-full bg-indigo-500" style={{ width: `${Math.max(4, (m.amount / maxSpend) * 100)}%` }} />
                </div>
              </div>
            ))}
          </div>
        </section>
      </div>

      {/* Recent Orders */}
      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <div className="flex justify-between">
          <h2 className="font-semibold text-slate-900">Recent Purchase Orders</h2>
          <Link to="/purchasing/orders" className="text-sm font-semibold text-indigo-600">View all</Link>
        </div>
        <div className="mt-4 divide-y">
          {data.recent_orders.length ? data.recent_orders.map((o) => (
            <div key={o.id} className="flex items-center justify-between py-3">
              <div>
                <p className="font-mono text-sm font-semibold text-slate-800">{o.po_number}</p>
                <p className="text-xs text-slate-500">{o.vendor_name} · PKR {Number(o.total_amount).toLocaleString()}</p>
              </div>
              <PurchasingStatusBadge status={o.status} />
            </div>
          )) : <p className="py-4 text-sm text-slate-400">No orders yet.</p>}
        </div>
      </section>

      {/* Recent Receipts */}
      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="font-semibold text-slate-900">Recent Goods Receipts</h2>
        <div className="mt-4 divide-y">
          {data.recent_receipts.length ? data.recent_receipts.map((r) => (
            <div key={r.id} className="flex items-center justify-between py-3">
              <div>
                <p className="font-mono text-sm font-semibold text-slate-800">{r.receipt_number}</p>
                <p className="text-xs text-slate-500">{r.po_number} · {r.vendor_name}</p>
              </div>
              <time className="text-xs text-slate-400">{new Date(r.received_at).toLocaleDateString()}</time>
            </div>
          )) : <p className="py-4 text-sm text-slate-400">No receipts yet.</p>}
        </div>
      </section>
    </div>
  );
};
