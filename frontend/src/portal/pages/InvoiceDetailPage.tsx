import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getInvoice, getInvoicePdf } from '../api/portalApi';
import { CardSkeleton } from '../components/PortalSkeleton';

const formatCurrency = (amount: number, currency = 'PKR'): string =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);

export const InvoiceDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const invoiceId = Number(id);

  const invoiceQuery = useQuery({
    queryKey: ['portal', 'invoice', invoiceId],
    queryFn: () => getInvoice(invoiceId),
    enabled: !Number.isNaN(invoiceId),
    staleTime: 60 * 1000,
  });

  const pdfQuery = useQuery({
    queryKey: ['portal', 'invoice', invoiceId, 'pdf'],
    queryFn: () => getInvoicePdf(invoiceId),
    enabled: !Number.isNaN(invoiceId),
    staleTime: 5 * 60 * 1000,
  });

  if (invoiceQuery.isLoading) {
    return <CardSkeleton rows={8} />;
  }

  if (invoiceQuery.error) {
    return (
      <Alert title="Unable to load invoice">
        {apiErrorMessage(invoiceQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  const invoice = invoiceQuery.data;
  if (!invoice) {
    return <Alert title="Invoice not found">The requested invoice could not be found.</Alert>;
  }

  const items = (invoice.items as Array<Record<string, unknown>>) ?? [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Invoice {invoice.invoice_number as string}
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            Issued {new Date(invoice.issue_date as string).toLocaleDateString()} · Due{' '}
            {new Date(invoice.due_date as string).toLocaleDateString()}
          </p>
        </div>
        <Button disabled={pdfQuery.isLoading}>
          {pdfQuery.isLoading ? 'Preparing PDF...' : 'Download PDF'}
        </Button>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</p>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
              {formatCurrency(Number(invoice.subtotal))}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Tax</p>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
              {formatCurrency(Number(invoice.tax_amount))}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Discount</p>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
              {formatCurrency(Number(invoice.discount_amount))}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total due</p>
            <p className="mt-1 text-lg font-semibold text-rose-600 dark:text-rose-400">
              {formatCurrency(Number(invoice.total_amount))}
            </p>
          </div>
        </div>
      </div>

      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card dark:border-slate-700 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead className="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Unit price</th>
                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
              {items.length === 0 ? (
                <tr>
                  <td colSpan={4} className="px-4 py-8 text-center text-sm text-slate-500">
                    No line items.
                  </td>
                </tr>
              ) : (
                items.map((item, index) => (
                  <tr key={index}>
                    <td className="px-4 py-3 text-sm text-slate-900 dark:text-white">
                      {item.description as string}
                      <span className="ml-2 text-xs text-slate-500 capitalize">({item.item_type as string})</span>
                    </td>
                    <td className="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300">
                      {item.quantity as number}
                    </td>
                    <td className="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-300">
                      {formatCurrency(Number(item.unit_price))}
                    </td>
                    <td className="px-4 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                      {formatCurrency(Number(item.amount))}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
