import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePurchaseOrders, useCreatePurchaseOrder } from '../api/usePurchasing';
import { PurchaseOrderTable } from '../components/PurchaseOrderTable';
import { PurchaseOrderForm } from '../components/PurchaseOrderForm';
import type { PurchaseOrderFormValues } from '../types';

export const PurchaseOrderListPage = () => {
  const [filters, setFilters] = useState<Record<string, string>>({ status: '', search: '' });
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const list = usePurchaseOrders(filters);
  const create = useCreatePurchaseOrder();

  const items = list.data?.data.map((r) => r.attributes) ?? [];

  const handleCreate = async (data: PurchaseOrderFormValues) => {
    setError(null);
    try {
      await create.mutateAsync(data);
      setShowForm(false);
    } catch (e) {
      setError(apiErrorMessage(e));
    }
  };

  return (
    <div className="space-y-6">
      <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Purchasing &amp; Procurement</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900">Purchase Orders</h1>
          <p className="mt-1 text-sm text-slate-500">Manage supplier orders from creation through receipt and closure.</p>
        </div>
        <div className="flex gap-2">
          <Link className="rounded-md border bg-white px-3 py-2 text-sm font-semibold text-slate-700" to="/purchasing">Dashboard</Link>
          <button onClick={() => setShowForm(!showForm)} className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {showForm ? 'Cancel' : '+ New Order'}
          </button>
        </div>
      </header>

      {error ? <Alert variant="danger" title="Error">{error}</Alert> : null}

      {showForm ? (
        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="mb-4 font-semibold text-slate-900">New Purchase Order</h2>
          <PurchaseOrderForm onSubmit={handleCreate} isLoading={create.isPending} />
        </section>
      ) : null}

      <section className="rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-200 px-5 py-4">
          <input
            placeholder="Search orders…"
            value={filters.search ?? ''}
            onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
            className="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <select value={filters.status ?? ''} onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="pending_approval">Pending Approval</option>
            <option value="approved">Approved</option>
            <option value="sent">Sent</option>
            <option value="partially_received">Partially Received</option>
            <option value="fully_received">Fully Received</option>
            <option value="closed">Closed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <PurchaseOrderTable items={items} isLoading={list.isLoading} />
      </section>
    </div>
  );
};
