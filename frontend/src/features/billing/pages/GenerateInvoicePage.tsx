import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';

import { generateInvoice } from '../api/billingApi';
import type { GenerateInvoiceData } from '../types';

export const GenerateInvoicePage = () => {
  const navigate = useNavigate();
  const { can } = usePermissions();

  if (!can('billing.generate')) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-8 text-center">
        <p className="text-slate-500">You do not have permission to generate invoices.</p>
      </div>
    );
  }

  const mutation = useMutation({
    mutationFn: (data: GenerateInvoiceData) => generateInvoice(data),
    onSuccess: (invoice) => {
      navigate(`/billing/${invoice.id}`);
    },
  });

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);
    mutation.mutate({
      connection_id: String(formData.get('connection_id')),
      billing_period_start: String(formData.get('billing_period_start') || ''),
      billing_period_end: String(formData.get('billing_period_end') || ''),
      issue_date: String(formData.get('issue_date') || ''),
      due_date: String(formData.get('due_date') || ''),
      notes: String(formData.get('notes') || ''),
    });
  };

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Generate Invoice</h1>
        <p className="mt-1 text-sm text-slate-500">
          Automatically generate an invoice for an active connection based on its billing schedule.
        </p>
      </div>

      {mutation.error && (
        <Alert title="Generation failed">{apiErrorMessage(mutation.error, 'Unable to generate invoice.')}</Alert>
      )}

      <form onSubmit={handleSubmit} className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Connection ID</label>
          <input
            name="connection_id"
            type="number"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            placeholder="Enter active connection ID"
          />
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Billing Period Start (optional)</label>
            <input
              name="billing_period_start"
              type="date"
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Billing Period End (optional)</label>
            <input
              name="billing_period_end"
              type="date"
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Issue Date (optional)</label>
            <input
              name="issue_date"
              type="date"
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Due Date (optional)</label>
            <input
              name="due_date"
              type="date"
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            />
          </div>
        </div>

        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
          <textarea
            name="notes"
            rows={3}
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
        </div>

        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={() => navigate('/billing')}>
            Cancel
          </Button>
          <Button type="submit" isLoading={mutation.isPending}>
            Generate Invoice
          </Button>
        </div>
      </form>
    </div>
  );
};
