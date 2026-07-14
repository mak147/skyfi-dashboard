import { useQuery } from '@tanstack/react-query';
import { financeApi } from '../api/financeApi';
import type { ChartOfAccount } from '../types';

export const ChartOfAccountsPage = () => {
  const { data, isLoading } = useQuery({
    queryKey: ['chartOfAccounts'],
    queryFn: () => financeApi.getChartOfAccounts().then((res) => res.data),
  });

  if (isLoading) return <div>Loading...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Chart of Accounts</h1>
      <div className="rounded-xl border border-slate-200 bg-white">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Number</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Type</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Normal Balance</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-slate-200">
            {data?.map((coa: ChartOfAccount) => (
              <tr key={coa.id}>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{coa.account_number}</td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{coa.name}</td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500 capitalize">{coa.type}</td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500 capitalize">{coa.normal_balance}</td>
              </tr>
            ))}
            {(!data || data.length === 0) && (
              <tr><td colSpan={4} className="px-6 py-4 text-center text-sm text-slate-500">No accounts found.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
