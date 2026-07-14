import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';

import { getInvoices } from '../api/billingApi';
import { BillingStatistics } from '../components/BillingStatistics';
import { InvoiceCard } from '../components/InvoiceCard';
import { InvoiceFilters } from '../components/InvoiceFilters';
import { InvoiceTable } from '../components/InvoiceTable';
import type { InvoiceFilters as Filters } from '../types';

export const InvoicesListPage = () => {
  const navigate = useNavigate();
  const { can } = usePermissions();
  const [sp, setSp] = useSearchParams();

  const page = Math.max(1, Number(sp.get('page') ?? 1));
  const sort = sp.get('sort') ?? '-created_at';
  const size = 15;

  const filters: Filters = {
    status: (sp.get('status') as Filters['status']) || undefined,
    due_from: sp.get('due_from') || undefined,
    due_to: sp.get('due_to') || undefined,
    search: sp.get('search') || undefined,
  };

  const [selected, setSelected] = useState<number[]>([]);

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
    queryKey: ['invoices', page, size, filters, sort],
    queryFn: () => getInvoices(page, size, filters, sort),
    staleTime: 30000,
  });

  const items = q.data?.data.map((x) => x.attributes) ?? [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Billing</h1>
          <p className="mt-1 text-sm text-slate-500">
            Generate and manage customer invoices, billing schedules, and collection status.
          </p>
        </div>
        <div className="flex gap-2">
          {can('billing.generate') && (
            <Button variant="secondary" onClick={() => navigate('/billing/bulk')}>
              Bulk Billing
            </Button>
          )}
          {can('billing.create') && (
            <Button onClick={() => navigate('/billing/new')}>Create Invoice</Button>
          )}
        </div>
      </div>

      <BillingStatistics />
      <InvoiceFilters filters={filters} onChange={(f) => set(f)} />

      {q.error && (
        <Alert title="Failed to load invoices">{apiErrorMessage(q.error, 'Unable to load invoices.')}</Alert>
      )}

      {q.isLoading ? (
        <div className="h-80 animate-pulse rounded-xl bg-slate-100" />
      ) : (
        <>
          <div className="hidden md:block">
            <InvoiceTable
              invoices={items}
              selected={selected}
              onSelect={setSelected}
              sort={sort}
              onSort={(s) => set(filters, page, s)}
            />
          </div>
          <div className="grid gap-4 md:hidden">
            {items.map((invoice) => (
              <InvoiceCard
                key={invoice.id}
                invoice={invoice}
                selected={selected.includes(invoice.id)}
                onSelect={(v) =>
                  setSelected(v ? [...selected, invoice.id] : selected.filter((x) => x !== invoice.id))
                }
              />
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
            <Button
              size="sm"
              variant="secondary"
              disabled={page >= q.data.meta.last_page}
              onClick={() => set(filters, page + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};
