import type { Voucher } from '../types';

interface VoucherTableProps {
  vouchers: Voucher[];
  isLoading: boolean;
  canRevoke: boolean;
  onRevoke: (id: number) => void;
}

export const VoucherTable = ({ vouchers, isLoading, canRevoke, onRevoke }: VoucherTableProps) => {
  const getStatusBadge = (status: Voucher['status']) => {
    switch (status) {
      case 'new':
        return <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">New</span>;
      case 'used':
        return <span className="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">Used</span>;
      case 'expired':
        return <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Expired</span>;
      case 'revoked':
        return <span className="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">Revoked</span>;
    }
  };

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3">Code</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Time Limit</th>
              <th className="px-4 py-3">Data Limit</th>
              <th className="px-4 py-3">Price</th>
              <th className="px-4 py-3">Expires</th>
              <th className="px-4 py-3">Used At</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {isLoading
              ? Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td colSpan={8} className="px-4 py-5">
                      <div className="h-5 animate-pulse rounded bg-slate-100" />
                    </td>
                  </tr>
                ))
              : null}
            {!isLoading && vouchers.length === 0 ? (
              <tr>
                <td colSpan={8} className="px-4 py-12 text-center text-sm text-slate-500">
                  No vouchers found.
                </td>
              </tr>
            ) : null}
            {!isLoading &&
              vouchers.map((v) => (
                <tr key={v.id} className="transition hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <span className="font-mono text-sm font-bold text-slate-900">{v.code}</span>
                  </td>
                  <td className="px-4 py-3">{getStatusBadge(v.status)}</td>
                  <td className="px-4 py-3 text-sm text-slate-600">{v.time_limit ?? 'Unlimited'}</td>
                  <td className="px-4 py-3 text-sm text-slate-600">
                    {v.data_limit_mb ? `${v.data_limit_mb} MB` : 'No limit'}
                  </td>
                  <td className="px-4 py-3 text-sm text-slate-600">
                    {v.price !== null ? `PKR ${v.price.toFixed(2)}` : '—'}
                  </td>
                  <td className="px-4 py-3 text-sm text-slate-600">
                    {v.expires_at ? new Date(v.expires_at).toLocaleDateString() : 'Never'}
                  </td>
                  <td className="px-4 py-3 text-sm text-slate-600">
                    {v.used_at ? new Date(v.used_at).toLocaleString() : '—'}
                  </td>
                  <td className="px-4 py-3 text-right">
                    {v.status === 'new' && canRevoke ? (
                      <button
                        type="button"
                        className="rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition"
                        onClick={() => onRevoke(v.id)}
                      >
                        Revoke
                      </button>
                    ) : null}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
