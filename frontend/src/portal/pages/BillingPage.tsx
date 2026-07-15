

import { useQuery } from '@tanstack/react-query';

import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getBalance } from '../api/portalApi';
import { InvoiceTable } from '../components/InvoiceTable';

const formatCurrency = (amount: number, currency = 'PKR'): string =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);

export const BillingPage = () => {
  const balanceQuery = useQuery({
    queryKey: ['portal', 'balance'],
    queryFn: getBalance,
    staleTime: 60 * 1000,
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Billing</h1>
          <p className="mt-1 text-sm text-slate-500">View your invoices and outstanding balance.</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-700 dark:bg-slate-900">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding balance</p>
          {balanceQuery.isLoading ? (
            <div className="mt-2 h-8 w-32 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
          ) : balanceQuery.error ? (
            <p className="mt-2 text-sm text-rose-600 dark:text-rose-400">
              {apiErrorMessage(balanceQuery.error, 'Unable to load balance')}
            </p>
          ) : (
            <p className="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
              {formatCurrency(balanceQuery.data?.outstanding_balance ?? 0)}
            </p>
          )}
        </div>
      </div>

      <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-slate-700 dark:bg-slate-900">
        <h2 className="text-sm font-semibold text-slate-900 dark:text-white">Online payment</h2>
        <p className="mt-2 text-sm text-slate-500">
          Online payment processing will be enabled when a payment gateway is configured.
        </p>
        <Button className="mt-4" disabled>
          Pay now
        </Button>
      </div>

      <div>
        <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Invoice history</h2>
        <InvoiceTable />
      </div>
    </div>
  );
};
