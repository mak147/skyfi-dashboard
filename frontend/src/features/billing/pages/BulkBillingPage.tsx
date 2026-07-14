import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';

import { bulkGenerateInvoices } from '../api/billingApi';

export const BulkBillingPage = () => {
  const navigate = useNavigate();
  const { can } = usePermissions();
  const [billingDate, setBillingDate] = useState('');
  const [connectionIds, setConnectionIds] = useState('');

  if (!can('billing.generate')) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-8 text-center">
        <p className="text-slate-500">You do not have permission to run bulk billing.</p>
      </div>
    );
  }

  const mutation = useMutation({
    mutationFn: () =>
      bulkGenerateInvoices({
        billing_date: billingDate || undefined,
        connection_ids: connectionIds
          ? connectionIds.split(',').map((s) => Number(s.trim())).filter(Boolean)
          : undefined,
      }),
  });

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Bulk Billing</h1>
        <p className="mt-1 text-sm text-slate-500">
          Generate invoices for all active connections with due billing schedules. Leave connection IDs empty to bill all eligible connections.
        </p>
      </div>

      {mutation.error && (
        <Alert title="Bulk billing failed">{apiErrorMessage(mutation.error, 'Unable to complete bulk billing.')}</Alert>
      )}

      {mutation.data && (
        <Alert title="Bulk billing complete" variant="success">
          Generated {mutation.data.generated} invoices. Failed: {mutation.data.failed}.
          {mutation.data.errors.length > 0 && (
            <ul className="mt-2 list-disc pl-5 text-sm">
              {mutation.data.errors.map((err, i) => (
                <li key={i}>
                  Connection {err.connection_id}: {err.error}
                </li>
              ))}
            </ul>
          )}
        </Alert>
      )}

      <div className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Billing Date (optional)</label>
          <input
            type="date"
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={billingDate}
            onChange={(e) => setBillingDate(e.target.value)}
          />
          <p className="mt-1 text-xs text-slate-400">Defaults to today. Invoices are generated for schedules where next bill date is on or before this date.</p>
        </div>

        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Connection IDs (optional)</label>
          <textarea
            rows={3}
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            placeholder="e.g. 101, 102, 103"
            value={connectionIds}
            onChange={(e) => setConnectionIds(e.target.value)}
          />
          <p className="mt-1 text-xs text-slate-400">Comma-separated connection IDs. Leave empty to process all eligible connections.</p>
        </div>

        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={() => navigate('/billing')}>
            Cancel
          </Button>
          <Button onClick={() => mutation.mutate()} isLoading={mutation.isPending}>
            Run Bulk Billing
          </Button>
        </div>
      </div>
    </div>
  );
};
