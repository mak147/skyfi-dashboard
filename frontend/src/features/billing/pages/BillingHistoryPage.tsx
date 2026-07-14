import { useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getInvoices } from '../api/billingApi';
import { InvoiceCard } from '../components/InvoiceCard';
import { InvoiceTable } from '../components/InvoiceTable';
import type { InvoiceFilters as Filters } from '../types';

export const BillingHistoryPage = () => {
  const [sp, setSp] = useSearchParams();
  const page = Math.max(1, Number(sp.get('page') ?? 1));
  const sort = sp.get('sort') ?? '-created_at';
  const size = 15;

  const filters: Filters = {
    status: (sp.get('status') as Filters['status']) || undefined,
    search: sp.get('search') || undefined,
  };

  const set = (f: Filters, p = 1, s = sort) => {
    const x = new URLSearchParams();
    if (p > 1) x.set('page', String(p));
    if (s !== '-created_at') x.set('sort', s);
    Object.entries(f).forEach(([k, v]) => {
      if (v) x.set(k, v);
    });
    setSp(x);
  };

  const q = useQuery({
    queryKey: ['invoices-history', page, size, filters, sort],
    queryFn: () => getInvoices(page, size, filters, sort),
    staleTime: 30000,
  });

  const items = q.data?.data.map((x) => x.attributes) ?? [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Billing History</h1>
        <p className="mt-1 text-sm text-slate-500">Browse all historical invoices across the system.</p>
      </div>

      <div className="flex flex-wrap gap-3">
        <select
          className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
          value={filters.status || ''}
          onChange={(e) => set({ ...filters, status: e.target.value as Filters['status'] })}
        >
          <option value="">All Statuses</option>
          <option value="draft">Draft</option>
          <option value="pending">Pending</option>
          <option value="issued">Issued</option>
          <option value="partially_paid">Partially Paid</option>
          <option value="paid">Paid</option>
          <option value="overdue">Overdue</option>
          <option value="cancelled">Cancelled</option>
          <option value="void">Void</option>
        </select>
        <input
          type="text"
          className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
          placeholder="Search invoices..."
          value={filters.search || ''}
          onChange={(e) => set({ ...filters, search: e.target.value })}
        />
      </div>

      {q.error && (
        <Alert title="Failed to load history">{apiErrorMessage(q.error, 'Unable to load billing history.')}</Alert>
      )}

      {q.isLoading ? (
        <div className="h-80 animate-pulse rounded-xl bg-slate-100" />
      ) : (
        <>
          <div className="hidden md:block">
            <InvoiceTable invoices={items} selected={[]} onSelect={() => {}} sort={sort} onSort={(s) => set(filters, page, s)} />
          </div>
          <div className="grid gap-4 md:hidden">
            {items.map((invoice) => (
              <InvoiceCard key={invoice.id} invoice={invoice} selected={false} onSelect={() => {}} />
            ))}
          </div>
        </>
      )}

      {q.data && (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3">
          <p className="text-sm text-slate-500">{q.data.meta.total} invoices</p>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={page <= 1} onClick={() => set(filters, page - 1)}>
              Previous
            </Button>
            <Button size="sm" variant="secondary" disabled={page >= q.data.meta.last_page} onClick={() => set(filters, page + 1)}>
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};
