import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePurchaseRequests, useCreatePurchaseRequest } from '../api/usePurchasing';
import { PurchaseRequestTable } from '../components/PurchaseRequestTable';
import { PurchaseRequestForm } from '../components/PurchaseRequestForm';
import type { PurchaseRequestFormValues } from '../types';

export const PurchaseRequestsPage = () => {
  const [filters, setFilters] = useState<Record<string, string>>({ status: '', search: '' });
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const list = usePurchaseRequests(filters);
  const create = useCreatePurchaseRequest();

  const items = list.data?.data.map((r) => r.attributes) ?? [];

  const handleCreate = async (data: PurchaseRequestFormValues) => {
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
          <h1 className="mt-2 text-2xl font-bold text-slate-900">Purchase Requests</h1>
          <p className="mt-1 text-sm text-slate-500">Create and manage procurement requests before they become purchase orders.</p>
        </div>
        <div className="flex gap-2">
          <Link className="rounded-md border bg-white px-3 py-2 text-sm font-semibold text-slate-700" to="/purchasing">Dashboard</Link>
          <button onClick={() => setShowForm(!showForm)} className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {showForm ? 'Cancel' : '+ New Request'}
          </button>
        </div>
      </header>

      {error ? <Alert variant="danger" title="Error">{error}</Alert> : null}

      {showForm ? (
        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="mb-4 font-semibold text-slate-900">New Purchase Request</h2>
          <PurchaseRequestForm onSubmit={handleCreate} isLoading={create.isPending} />
        </section>
      ) : null}

      <section className="rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-200 px-5 py-4">
          <input
            placeholder="Search requests…"
            value={filters.search ?? ''}
            onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
            className="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <select value={filters.status ?? ''} onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="pending_approval">Pending Approval</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
            <option value="converted">Converted</option>
          </select>
        </div>
        <PurchaseRequestTable items={items} isLoading={list.isLoading} />
      </section>
    </div>
  );
};
