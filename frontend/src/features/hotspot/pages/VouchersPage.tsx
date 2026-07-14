import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useVouchers, useVoucherBatches, useVoucherStats, revokeVoucher } from '../api/useHotspot';
import { VoucherTable } from '../components/VoucherTable';
import type { VoucherStatus } from '../types';

export const VouchersPage = () => {
  const navigate = useNavigate();
  const { can } = usePermissions();
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState<VoucherStatus | ''>('');

  const { data: stats } = useVoucherStats();
  const { data: vouchersResponse, isLoading, error } = useVouchers(page, 20, statusFilter || undefined);
  const { data: batchesResponse } = useVoucherBatches(1, 10);

  const revokeMutation = useMutation({
    mutationFn: (id: number) => revokeVoucher(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'vouchers'] });
      queryClient.invalidateQueries({ queryKey: ['hotspot', 'voucher-stats'] });
    },
  });

  const vouchers = vouchersResponse?.data.map((i) => i.attributes) ?? [];
  const meta = vouchersResponse?.meta;
  const batches = batchesResponse?.data.map((i) => i.attributes) ?? [];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Voucher Management</h1>
          <p className="mt-1 text-sm text-slate-500">
            Generate, track, and manage prepaid hotspot access vouchers.
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => navigate('/hotspot')}>
            Back to Users
          </Button>
          {can('hotspot.vouchers') ? (
            <Button onClick={() => navigate('/hotspot/vouchers/generate')}>Generate Batch</Button>
          ) : null}
        </div>
      </div>

      {/* Stats Cards */}
      {stats ? (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
          {[
            { label: 'Available', value: stats.total_new, color: 'emerald' },
            { label: 'Used', value: stats.total_used, color: 'indigo' },
            { label: 'Expired', value: stats.total_expired, color: 'slate' },
            { label: 'Revoked', value: stats.total_revoked, color: 'red' },
            { label: 'Today Logins', value: stats.daily_logins, color: 'amber' },
          ].map((stat) => (
            <div key={stat.label} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
              <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{stat.label}</p>
              <p className={`mt-1 text-2xl font-bold text-${stat.color}-700`}>{stat.value}</p>
            </div>
          ))}
        </div>
      ) : null}

      {/* Recent Batches */}
      {batches.length > 0 ? (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h3 className="text-sm font-semibold text-slate-900 mb-3">Recent Voucher Batches</h3>
          <div className="space-y-2">
            {batches.slice(0, 5).map((batch) => (
              <div key={batch.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-3">
                <div>
                  <p className="font-mono text-sm font-semibold text-slate-800">{batch.batch_code}</p>
                  <p className="text-xs text-slate-500">
                    {batch.profile_name ?? 'Profile'} • {batch.router_name ?? `Router #${batch.router_id}`}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-sm font-semibold text-slate-800">{batch.quantity} vouchers</p>
                  <p className="text-xs text-slate-500">
                    {batch.time_limit ?? 'Unlimited'} •{' '}
                    {batch.data_limit_mb ? `${batch.data_limit_mb} MB` : 'No data limit'}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      ) : null}

      {/* Voucher List Filter */}
      <div className="flex items-center gap-4">
        <h3 className="text-sm font-semibold text-slate-900">Individual Vouchers</h3>
        <select
          value={statusFilter}
          onChange={(e) => {
            setStatusFilter(e.target.value as VoucherStatus | '');
            setPage(1);
          }}
          className="rounded-md border border-slate-300 px-2 py-1 text-sm"
        >
          <option value="">All Statuses</option>
          <option value="new">New</option>
          <option value="used">Used</option>
          <option value="expired">Expired</option>
          <option value="revoked">Revoked</option>
        </select>
      </div>

      {error ? (
        <Alert title="Unable to load vouchers" variant="danger">
          {apiErrorMessage(error)}
        </Alert>
      ) : null}
      {revokeMutation.error ? (
        <Alert title="Revoke failed" variant="danger">
          {apiErrorMessage(revokeMutation.error)}
        </Alert>
      ) : null}

      {/* Using dedicated VoucherTable component */}
      <VoucherTable
        vouchers={vouchers}
        isLoading={isLoading}
        canRevoke={can('hotspot.vouchers')}
        onRevoke={(id) => revokeMutation.mutate(id)}
      />

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between border-t border-slate-200 pt-4">
          <p className="text-sm text-slate-500">
            Page {meta.current_page} of {meta.last_page} ({meta.total} total)
          </p>
          <div className="flex gap-2">
            <Button size="sm" variant="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>
              Previous
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={page >= meta.last_page}
              onClick={() => setPage(page + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
