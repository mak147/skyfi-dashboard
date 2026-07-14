import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useGenerateVoucherBatch } from '../api/useHotspot';
import { generateVoucherBatchSchema, type GenerateVoucherBatchValues } from '../schemas';

interface VoucherGeneratorProps {
  onSuccess?: () => void;
  onCancel?: () => void;
}

export const VoucherGenerator = ({ onSuccess, onCancel }: VoucherGeneratorProps) => {
  const generateMutation = useGenerateVoucherBatch();

  const form = useForm<GenerateVoucherBatchValues>({
    resolver: zodResolver(generateVoucherBatchSchema),
    defaultValues: {
      hotspot_profile_id: 0,
      router_id: 0,
      quantity: 10,
      prefix: '',
      time_limit: '',
      validity_days: 30,
      notes: '',
    },
  });

  const onSubmit = (data: GenerateVoucherBatchValues) => {
    generateMutation.mutate(data, { onSuccess });
  };

  return (
    <div className="space-y-4">
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="text-lg font-bold text-slate-900 mb-4">Generate Voucher Batch</h2>
        <p className="text-sm text-slate-500 mb-6">
          Create a batch of unique, printable hotspot access vouchers with cryptographically secure codes.
        </p>

        {generateMutation.error ? (
          <Alert title="Failed to generate vouchers" variant="danger">
            {apiErrorMessage(generateMutation.error)}
          </Alert>
        ) : null}

        {generateMutation.isSuccess ? (
          <Alert title="Voucher batch generated successfully!" variant="success">
            Batch {generateMutation.data?.batch_code} created with {generateMutation.data?.quantity} vouchers.
          </Alert>
        ) : null}

        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-semibold text-slate-700">Hotspot Profile ID *</label>
              <input
                type="number"
                {...form.register('hotspot_profile_id', { valueAsNumber: true })}
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              {form.formState.errors.hotspot_profile_id ? (
                <p className="mt-1 text-xs text-red-600">{form.formState.errors.hotspot_profile_id.message}</p>
              ) : null}
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Router ID *</label>
              <input
                type="number"
                {...form.register('router_id', { valueAsNumber: true })}
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              {form.formState.errors.router_id ? (
                <p className="mt-1 text-xs text-red-600">{form.formState.errors.router_id.message}</p>
              ) : null}
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Quantity * (1–1000)</label>
              <input
                type="number"
                min={1}
                max={1000}
                {...form.register('quantity', { valueAsNumber: true })}
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              {form.formState.errors.quantity ? (
                <p className="mt-1 text-xs text-red-600">{form.formState.errors.quantity.message}</p>
              ) : null}
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Prefix (uppercase, max 10)</label>
              <input
                {...form.register('prefix')}
                placeholder="e.g. WIFI"
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm uppercase focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Time Limit (e.g. 1h, 1d)</label>
              <input
                {...form.register('time_limit')}
                placeholder="e.g. 1d"
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Data Limit (MB)</label>
              <input
                type="number"
                {...form.register('data_limit_mb', { valueAsNumber: true })}
                placeholder="e.g. 1024"
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Price per Voucher (PKR)</label>
              <input
                type="number"
                step="0.01"
                {...form.register('price_per_voucher', { valueAsNumber: true })}
                placeholder="0.00"
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700">Validity (days from generation)</label>
              <input
                type="number"
                {...form.register('validity_days', { valueAsNumber: true })}
                placeholder="30"
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-semibold text-slate-700">Notes</label>
            <textarea
              {...form.register('notes')}
              rows={2}
              className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div className="rounded-lg bg-indigo-50 p-3 text-xs text-indigo-800">
            <strong>Preview:</strong> Voucher codes will look like{' '}
            <span className="font-mono font-bold">
              {form.watch('prefix') || ''}XXXXXXXX
            </span>{' '}
            (8 random characters from ambiguous-free alphabet: A–Z, 2–9, excluding I, O, 0, 1)
          </div>

          <div className="flex justify-end gap-2 pt-4 border-t border-slate-200">
            {onCancel ? (
              <Button variant="secondary" type="button" onClick={onCancel}>
                Cancel
              </Button>
            ) : null}
            <Button type="submit" disabled={generateMutation.isPending}>
              {generateMutation.isPending ? 'Generating...' : `Generate ${form.watch('quantity') || 10} Vouchers`}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
