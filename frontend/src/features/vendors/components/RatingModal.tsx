import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { vendorRatingSchema } from '../schemas';
import { useCreateVendorRating } from '../api/useVendors';
import type { VendorRatingFormValues } from '../types';

interface RatingModalProps {
  vendorId: number;
  isOpen: boolean;
  onClose: () => void;
}

export const RatingModal: React.FC<RatingModalProps> = ({ vendorId, isOpen, onClose }) => {
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const mutation = useCreateVendorRating();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<VendorRatingFormValues>({
    resolver: zodResolver(vendorRatingSchema),
    defaultValues: {
      vendor_id: vendorId,
      evaluation_date: new Date().toISOString().split('T')[0],
      delivery_performance: 100,
      order_completion: 100,
      product_quality: 100,
      return_rate: 0,
      average_lead_time_days: 7,
      comments: '',
    },
  });

  if (!isOpen) return null;

  const onSubmit = async (data: VendorRatingFormValues) => {
    setErrorMsg(null);
    try {
      await mutation.mutateAsync({ vendorId, data: { ...data, vendor_id: vendorId } });
      reset();
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Failed to submit performance evaluation.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
      <div className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
          <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100">Submit Performance Evaluation</h3>
          <button onClick={onClose} className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
            ✕
          </button>
        </div>

        {errorMsg && (
          <div className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-300">
            {errorMsg}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} className="mt-4 space-y-4">
          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Evaluation Date</label>
            <input
              type="date"
              {...register('evaluation_date')}
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            {errors.evaluation_date && <p className="mt-1 text-xs text-red-600">{errors.evaluation_date.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Delivery Performance (%)</label>
              <input
                type="number"
                step="0.1"
                {...register('delivery_performance', { valueAsNumber: true })}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.delivery_performance && <p className="mt-1 text-xs text-red-600">{errors.delivery_performance.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Order Completion (%)</label>
              <input
                type="number"
                step="0.1"
                {...register('order_completion', { valueAsNumber: true })}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.order_completion && <p className="mt-1 text-xs text-red-600">{errors.order_completion.message}</p>}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Product Quality (%)</label>
              <input
                type="number"
                step="0.1"
                {...register('product_quality', { valueAsNumber: true })}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.product_quality && <p className="mt-1 text-xs text-red-600">{errors.product_quality.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Defect/Return Rate (%)</label>
              <input
                type="number"
                step="0.1"
                {...register('return_rate', { valueAsNumber: true })}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.return_rate && <p className="mt-1 text-xs text-red-600">{errors.return_rate.message}</p>}
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Average Lead Time (Days)</label>
            <input
              type="number"
              {...register('average_lead_time_days', { valueAsNumber: true })}
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            {errors.average_lead_time_days && <p className="mt-1 text-xs text-red-600">{errors.average_lead_time_days.message}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Evaluation Comments</label>
            <textarea
              rows={3}
              {...register('comments')}
              placeholder="Notes on supplier fulfillment, communication, or warranty responsiveness..."
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <Button type="button" variant="secondary" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || mutation.isPending}>
              {mutation.isPending ? 'Submitting...' : 'Submit Evaluation'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
