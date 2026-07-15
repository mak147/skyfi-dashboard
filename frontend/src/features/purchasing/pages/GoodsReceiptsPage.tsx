import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/alert';
import { useGoodsReceipts } from '../api/usePurchasing';
import type { GoodsReceipt } from '../types';
import { PurchasingStatusBadge } from '../components/PurchasingStatusBadge';

export const GoodsReceiptsPage = () => {
  const [filters, setFilters] = useState<Record<string, string>>({ status: '', search: '' });
  const list = useGoodsReceipts(filters);
  const [showReceiveForm, setShowReceiveForm] = useState(false);
  const [poId, setPoId] = useState('');
  const [error, setError] = useState<string | null>(null);

  const items: GoodsReceipt[] = list.data?.data.map((r) => r.attributes) ?? [];

  const handleQuickReceive = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    const id = parseInt(poId);
    if (!id || id < 1) {
      setError('Enter a valid Purchase Order ID.');
      return;
    }
    // Navigate to order detail for full receipt flow
    window.location.href = `/purchasing/orders/${id}`;
  };

  return (
    <div className="space-y-6">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Purchasing &amp; Procurement</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900">Goods Receipts</h1>
          <p className="mt-1 text-sm text-slate-500">Track received goods, damages, short receipts, and returns.</p>
        </div>
        <div className="flex gap-2">
          <Link className="rounded-md border bg-white px-3 py-2 text-sm font-semibold text-slate-700" to="/purchasing">Dashboard</Link>
          <button onClick={() => setShowReceiveForm(!showReceiveForm)} className="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            {showReceiveForm ? 'Cancel' : '+ Receive Goods'}
          </button>
        </div>
      </header>

      {error ? <Alert variant="danger" title="Error">{error}</Alert> : null}

      {showReceiveForm ? (
        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="mb-4 font-semibold text-slate-900">Receive Goods from Purchase Order</h2>
          <form onSubmit={handleQuickReceive} className="flex items-end gap-3">
            <div className="flex-1">
              <label className="mb-1 block text-sm font-semibold text-slate-700">Purchase Order ID</label>
              <input type="number" min={1} value={poId} onChange={(e) => setPoId(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Enter PO ID" />
            </div>
            <button type="submit" className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Continue</button>
          </form>
          <p className="mt-3 text-xs text-slate-500">Enter a Purchase Order ID to begin the goods receipt process. The receipt form will pre-fill from PO line items.</p>
        </section>
      ) : null}

      <section className="rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-200 px-5 py-4">
          <input
            placeholder="Search receipts…"
            value={filters.search ?? ''}
            onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
            className="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <select value={filters.status ?? ''} onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="received">Received</option>
            <option value="partial">Partial</option>
            <option value="returned">Returned</option>
          </select>
        </div>
        {list.isLoading ? (
          <div className="space-y-3 p-5">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="h-14 animate-pulse rounded-lg bg-slate-100" />)}</div>
        ) : items.length === 0 ? (
          <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"><p className="text-sm text-slate-500">No goods receipts found.</p></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-4 py-3">Receipt #</th>
                  <th className="px-4 py-3">PO #</th>
                  <th className="px-4 py-3">Supplier</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Received By</th>
                  <th className="px-4 py-3">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {items.map((r) => (
                  <tr key={r.id} className="hover:bg-slate-50">
                    <td className="px-4 py-3 font-mono font-semibold text-slate-800">{r.receipt_number}</td>
                    <td className="px-4 py-3 font-mono text-slate-600">{r.po_number}</td>
                    <td className="px-4 py-3 text-slate-700">{r.vendor_name}</td>
                    <td className="px-4 py-3"><PurchasingStatusBadge status={r.status} /></td>
                    <td className="px-4 py-3 text-slate-600">{r.received_by_name}</td>
                    <td className="px-4 py-3 text-xs text-slate-400">{new Date(r.received_at).toLocaleString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
};
