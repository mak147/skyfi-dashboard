import { useQuery } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { financeApi } from '../api/financeApi';

export const FinanceDashboardPage = () => {
  const query = useQuery({
    queryKey: ['financeDashboard'],
    queryFn: () => financeApi.getDashboardStats().then((res) => res.data),
  });

  if (query.isLoading) {
    return <div>Loading finance dashboard...</div>;
  }

  if (query.error || !query.data) {
    return (
      <Alert title="Error">
        Unable to load the finance dashboard.
      </Alert>
    );
  }

  const { data } = query;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Finance & Accounting</h1>
      
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <p className="text-sm text-slate-500">Total Cash Balance</p>
          <p className="mt-2 text-2xl font-bold">PKR {data.cash_balance?.toLocaleString() || '0'}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <p className="text-sm text-slate-500">Total Bank Balance</p>
          <p className="mt-2 text-2xl font-bold">PKR {data.bank_balance?.toLocaleString() || '0'}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <p className="text-sm text-slate-500">Revenue This Month</p>
          <p className="mt-2 text-2xl font-bold text-emerald-600">PKR {data.revenue_this_month?.toLocaleString() || '0'}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <p className="text-sm text-slate-500">Expenses This Month</p>
          <p className="mt-2 text-2xl font-bold text-rose-600">PKR {data.expenses_this_month?.toLocaleString() || '0'}</p>
        </div>
      </div>
    </div>
  );
};
