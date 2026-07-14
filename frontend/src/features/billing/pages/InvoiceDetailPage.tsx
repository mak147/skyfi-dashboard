import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { clsx } from 'clsx';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';

import { changeInvoiceStatus, deleteInvoice, getInvoice } from '../api/billingApi';
import { BillingSummary } from '../components/BillingSummary';
import { InvoiceActivityTimeline } from '../components/InvoiceActivityTimeline';
import { InvoiceStatusBadge } from '../components/InvoiceStatusBadge';

const tabs = ['overview', 'items', 'activity'] as const;

export const InvoiceDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const [tab, setTab] = useState<(typeof tabs)[number]>('overview');

  const q = useQuery({
    queryKey: ['invoice', id],
    queryFn: () => getInvoice(Number(id)),
    enabled: id !== undefined && id !== '',
    staleTime: 30000,
  });

  const statusMutation = useMutation({
    mutationFn: (s: string) => changeInvoiceStatus(Number(id), s),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['invoice', id] });
      void queryClient.invalidateQueries({ queryKey: ['invoices'] });
    },
  });

  const delMutation = useMutation({
    mutationFn: () => deleteInvoice(Number(id)),
    onSuccess: () => navigate('/billing'),
  });

  if (q.isLoading) {
    return (
      <div className="space-y-5">
        <div className="h-52 animate-pulse rounded-3xl bg-slate-100" />
        <div className="h-80 animate-pulse rounded-xl bg-slate-100" />
      </div>
    );
  }

  if (q.error || !q.data) {
    return (
      <Alert title="Failed to load invoice">{apiErrorMessage(q.error, 'Invoice not found.')}</Alert>
    );
  }

  const invoice = q.data;

  const statusOptions = ['draft', 'pending', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled', 'void'];

  return (
    <div className="space-y-6">
      <section className="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 to-slate-900 p-7 text-white shadow-card">
        <div className="flex flex-col gap-5 sm:flex-row sm:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[.2em] text-indigo-100">
              {invoice.invoice_number}
            </p>
            <h1 className="mt-2 text-3xl font-bold">{invoice.customer_name || 'Customer'}</h1>
            <p className="mt-3 max-w-2xl text-sm text-indigo-100">
              {invoice.connection_number ? `Connection: ${invoice.connection_number}` : 'No connection linked.'}
              {invoice.package_name ? ` · Package: ${invoice.package_name}` : ''}
            </p>
            <div className="mt-4">
              <InvoiceStatusBadge status={invoice.status} />
            </div>
          </div>
          <div className="flex flex-wrap items-start gap-2">
            {can('billing.update') && ['draft', 'pending'].includes(invoice.status) && (
              <Button className="bg-white text-indigo-700 hover:bg-indigo-50" onClick={() => navigate(`/billing/${id}/edit`)}>
                Edit
              </Button>
            )}
            {can('billing.manage') && (
              <select
                className="h-10 rounded-md border border-white/30 bg-white px-3 text-sm text-slate-800"
                value={invoice.status}
                onChange={(e) => statusMutation.mutate(e.target.value)}
              >
                {statusOptions.map((s) => (
                  <option key={s} value={s}>
                    {s.replace('_', ' ')}
                  </option>
                ))}
              </select>
            )}
            {can('billing.delete') && (
              <Button variant="danger" onClick={() => confirm(`Delete invoice ${invoice.invoice_number}?`) && delMutation.mutate()}>
                Delete
              </Button>
            )}
          </div>
        </div>
      </section>

      {(statusMutation.error || delMutation.error) && (
        <Alert title="Action failed">
          {apiErrorMessage(statusMutation.error || delMutation.error, 'Unable to update the invoice.')}
        </Alert>
      )}

      <nav className="flex overflow-x-auto border-b border-slate-200" aria-label="Invoice sections">
        {tabs.map((t) => (
          <button
            key={t}
            className={clsx(
              'whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold capitalize',
              tab === t ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500',
            )}
            onClick={() => setTab(t)}
          >
            {t}
          </button>
        ))}
      </nav>

      {tab === 'overview' && (
        <div className="grid gap-5 lg:grid-cols-3">
          <div className="lg:col-span-2 space-y-6">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Customer</h3>
              <div className="mt-4 space-y-3">
                <div>
                  <p className="text-xs text-slate-400">Name</p>
                  <p className="text-sm font-medium text-slate-900">{invoice.customer_name || '—'}</p>
                </div>
                <div>
                  <p className="text-xs text-slate-400">Customer Code</p>
                  <p className="text-sm font-medium text-slate-900">{invoice.customer_code || '—'}</p>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  className="w-full justify-start"
                  onClick={() => navigate(`/customers/${invoice.customer_id}`)}
                >
                  View Customer Profile
                </Button>
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Connection & Package</h3>
              <div className="mt-4 space-y-3">
                <div>
                  <p className="text-xs text-slate-400">Connection</p>
                  <p className="text-sm font-medium text-slate-900">{invoice.connection_number || '—'}</p>
                </div>
                <div>
                  <p className="text-xs text-slate-400">Package</p>
                  <p className="text-sm font-medium text-slate-900">{invoice.package_name || '—'}</p>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  className="w-full justify-start"
                  onClick={() => navigate(`/connections/${invoice.connection_id}`)}
                >
                  View Connection
                </Button>
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <p className="font-semibold text-slate-700">Payment History</p>
              <p className="mt-2 text-sm text-slate-500">
                View all receipts and allocations recorded against this invoice.
              </p>
              {can('payments.view') && (
                <Button className="mt-4" variant="secondary" onClick={() => navigate(`/payments?invoice_id=${invoice.id}`)}>
                  View Invoice Payments
                </Button>
              )}
            </div>
          </div>
          <BillingSummary invoice={invoice} />
        </div>
      )}

      {tab === 'items' && (
        <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
          <table className="min-w-full">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs uppercase text-slate-500">Type</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-slate-500">Description</th>
                <th className="px-4 py-3 text-right text-xs uppercase text-slate-500">Qty</th>
                <th className="px-4 py-3 text-right text-xs uppercase text-slate-500">Unit Price</th>
                <th className="px-4 py-3 text-right text-xs uppercase text-slate-500">Amount</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {invoice.items.map((item) => (
                <tr key={item.id}>
                  <td className="px-4 py-3 text-sm capitalize text-slate-600">{item.item_type.replace('_', ' ')}</td>
                  <td className="px-4 py-3 text-sm font-medium text-slate-900">{item.description}</td>
                  <td className="px-4 py-3 text-right text-sm text-slate-600">{item.quantity}</td>
                  <td className="px-4 py-3 text-right text-sm tabular-nums text-slate-600">
                    {invoice.currency} {Number(item.unit_price).toLocaleString()}
                  </td>
                  <td className="px-4 py-3 text-right text-sm font-semibold tabular-nums text-slate-900">
                    {invoice.currency} {Number(item.amount).toLocaleString()}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {invoice.items.length === 0 && (
            <p className="py-16 text-center text-sm text-slate-500">No items on this invoice.</p>
          )}
        </div>
      )}

      {tab === 'activity' && (
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Activity Timeline</h3>
          <div className="mt-4">
            <InvoiceActivityTimeline invoiceId={Number(id)} />
          </div>
        </div>
      )}
    </div>
  );
};
