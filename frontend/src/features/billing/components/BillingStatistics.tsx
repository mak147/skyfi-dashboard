import { useQuery } from '@tanstack/react-query';
import { clsx } from 'clsx';
import { getBillingStatistics } from '../api/billingApi';

const Stat = ({ label, value, accent }: { label: string; value: string | number; accent: string }) => {
  const accentMap: Record<string, string> = {
    indigo: 'bg-indigo-50 text-indigo-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    red: 'bg-red-50 text-red-700',
    slate: 'bg-slate-50 text-slate-700',
  };

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">{label}</p>
      <p className={clsx('mt-2 text-2xl font-bold tabular-nums', accentMap[accent] ?? accentMap.slate)}>
        {value}
      </p>
    </div>
  );
};

export const BillingStatistics = () => {
  const { data, isLoading } = useQuery({
    queryKey: ['billing-statistics'],
    queryFn: getBillingStatistics,
    staleTime: 30000,
  });

  if (isLoading || !data) {
    return (
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-24 animate-pulse rounded-xl bg-slate-100" />
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
      <Stat label="Invoices Today" value={data.invoices_today} accent="indigo" />
      <Stat label="This Month" value={data.invoices_this_month} accent="indigo" />
      <Stat label="Pending" value={data.pending_invoices} accent="amber" />
      <Stat label="Paid" value={data.paid_invoices} accent="emerald" />
      <Stat label="Overdue" value={data.overdue_invoices} accent="red" />
    </div>
  );
};
