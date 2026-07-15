import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getPayment, getPaymentReceipt } from '../api/portalApi';
import { CardSkeleton } from '../components/PortalSkeleton';

const formatCurrency = (amount: number, currency = 'PKR'): string =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);

export const PaymentDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const paymentId = Number(id);

  const paymentQuery = useQuery({
    queryKey: ['portal', 'payment', paymentId],
    queryFn: () => getPayment(paymentId),
    enabled: !Number.isNaN(paymentId),
    staleTime: 60 * 1000,
  });

  const receiptQuery = useQuery({
    queryKey: ['portal', 'payment', paymentId, 'receipt'],
    queryFn: () => getPaymentReceipt(paymentId),
    enabled: !Number.isNaN(paymentId),
    staleTime: 5 * 60 * 1000,
  });

  if (paymentQuery.isLoading) {
    return <CardSkeleton rows={6} />;
  }

  if (paymentQuery.error) {
    return (
      <Alert title="Unable to load payment">
        {apiErrorMessage(paymentQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  const payment = paymentQuery.data;
  if (!payment) {
    return <Alert title="Payment not found">The requested payment could not be found.</Alert>;
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Payment {payment.payment_number as string}
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            {new Date(payment.payment_date as string).toLocaleString()} · {payment.payment_method_name as string}
          </p>
        </div>
        <Button disabled={receiptQuery.isLoading}>{receiptQuery.isLoading ? 'Preparing receipt...' : 'Download receipt'}</Button>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</p>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
              {formatCurrency(Number(payment.amount))}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
            <p className="mt-1 text-lg font-semibold capitalize text-slate-900 dark:text-white">
              {payment.status as string}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Applied</p>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
              {formatCurrency(Number(payment.applied_amount))}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Receipt number</p>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
              {(payment.receipt_number as string) ?? '—'}
            </p>
          </div>
        </div>
        {typeof payment.reference_number === 'string' && payment.reference_number && (
          <div className="mt-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</p>
            <p className="mt-1 text-sm text-slate-700 dark:text-slate-300">{payment.reference_number}</p>
          </div>
        )}
        {typeof payment.notes === 'string' && payment.notes && (
          <div className="mt-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</p>
            <p className="mt-1 text-sm text-slate-700 dark:text-slate-300">{payment.notes}</p>
          </div>
        )}
      </div>
    </div>
  );
};
