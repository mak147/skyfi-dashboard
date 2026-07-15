import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { useSupplierInvoices, useCreateSupplierInvoice } from '../api/usePurchasing';
import { SupplierInvoiceForm } from '../components/SupplierInvoiceForm';
import type { SupplierInvoice, SupplierInvoiceFormValues } from '../types';
import { PurchasingStatusBadge } from '../components/PurchasingStatusBadge';

export const SupplierInvoicesPage = () => {
  const [filters, setFilters] = useState<Record<string, string>>({ status: '', search: '' });
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const list = useSupplierInvoices(filters);
  const create = useCreateSupplierInvoice();

  const items: SupplierInvoice[] = list.data?.data.map((r) => r.attributes) ?? [];

  const handleCreate = async (data: SupplierInvoiceFormValues) => {
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
          <h1 className="mt-2 text-2xl font-bold text-slate-900">Supplier Invoices</h1>
          <p className="mt-1 text-sm text-slate-500">Register and track supplier invoices against purchase orders.</p>
        </div>
        <div className="flex gap-2">
          <Link className="rounded-md border bg-white px-3 py-2 text-sm font-semibold text-slate-700" to="/purchasing">Dashboard</Link>
          <button onClick={() => setShowForm(!showForm)} className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {showForm ? 'Cancel' : '+ Register Invoice'}
          </button>
        </div>
      </header>

      {error ? <Alert variant="danger" title="Error">{error}</Alert> : null}

      {showForm ? (
        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="mb-4 font-semibold text-slate-900">Register Supplier Invoice</h2>
          <SupplierInvoiceForm onSubmit={handleCreate} isLoading={create.isPending} />
        </section>
      ) : null}

      <section className="rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-200 px-5 py-4">
          <input
            placeholder="Search invoices…"
            value={filters.search ?? ''}
            onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
            className="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <select value={filters.status ?? ''} onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="registered">Registered</option>
            <option value="verified">Verified</option>
            <option value="disputed">Disputed</option>
            <option value="paid">Paid</option>
          </select>
        </div>
        {list.isLoading ? (
          <div className="space-y-3 p-5">{Array.from({ length: 5 }).map((_, i) => <div key={i} className="h-14 animate-pulse rounded-lg bg-slate-100" />)}</div>
        ) : items.length === 0 ? (
          <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"><p className="text-sm text-slate-500">No supplier invoices found.</p></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-4 py-3">Invoice #</th>
                  <th className="px-4 py-3">Supplier</th>
                  <th className="px-4 py-3">PO #</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Amount</th>
                  <th className="px-4 py-3">Invoice Date</th>
                  <th className="px-4 py-3">Due Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {items.map((inv) => (
                  <tr key={inv.id} className="hover:bg-slate-50">
                    <td className="px-4 py-3 font-mono font-semibold text-slate-800">{inv.invoice_number}</td>
                    <td className="px-4 py-3 text-slate-700">{inv.vendor_name}</td>
                    <td className="px-4 py-3 font-mono text-slate-500">{inv.po_number ?? '—'}</td>
                    <td className="px-4 py-3"><PurchasingStatusBadge status={inv.status} /></td>
                    <td className="px-4 py-3 font-semibold tabular-nums">{inv.currency} {Number(inv.total_amount).toLocaleString()}</td>
                    <td className="px-4 py-3 text-slate-600">{inv.invoice_date}</td>
                    <td className="px-4 py-3 text-slate-600">{inv.due_date ?? '—'}</td>
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
