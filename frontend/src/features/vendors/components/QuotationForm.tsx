import React, { useState } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { vendorQuotationSchema } from '../schemas';
import { useCreateVendorQuotation, useVendors } from '../api/useVendors';
import type { VendorQuotationFormValues } from '../types';

interface QuotationFormProps {
  vendorId?: number;
  isOpen: boolean;
  onClose: () => void;
}

export const QuotationForm: React.FC<QuotationFormProps> = ({ vendorId, isOpen, onClose }) => {
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const createMutation = useCreateVendorQuotation();
  const { data: vendorsData } = useVendors({ per_page: 100 });
  const vendorList = vendorsData?.data.map((v) => v.attributes) || [];

  const {
    register,
    control,
    handleSubmit,
    watch,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<VendorQuotationFormValues>({
    resolver: zodResolver(vendorQuotationSchema),
    defaultValues: {
      vendor_id: vendorId || undefined,
      rfq_number: `RFQ-${Math.floor(100 + Math.random() * 900)}`,
      quotation_number: `QT-${Math.floor(1000 + Math.random() * 9000)}`,
      quotation_date: new Date().toISOString().split('T')[0],
      validity_date: new Date(Date.now() + 2592000000).toISOString().split('T')[0],
      currency: 'PKR',
      status: 'received',
      total_amount: 0,
      notes: '',
      items: [{ description: '', quantity: 1, unit_price: 0 }],
    },
  });

  const { fields, append, remove } = useFieldArray({ control, name: 'items' });
  const watchedItems = watch('items') || [];

  const calculatedTotal = watchedItems.reduce((sum, item) => {
    const qty = Number(item?.quantity || 0);
    const up = Number(item?.unit_price || 0);
    return sum + qty * up;
  }, 0);

  if (!isOpen) return null;

  const onSubmit = async (data: VendorQuotationFormValues) => {
    setErrorMsg(null);
    const targetVendorId = Number(data.vendor_id || vendorId);
    if (!targetVendorId) {
      setErrorMsg('Please select a supplier for this quotation.');
      return;
    }

    try {
      await createMutation.mutateAsync({
        vendorId: targetVendorId,
        data: {
          ...data,
          vendor_id: targetVendorId,
          total_amount: calculatedTotal,
        },
      });
      reset();
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Failed to record quotation.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
      <div className="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
          <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100">Record Supplier Quotation / RFQ Response</h3>
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
          {!vendorId && (
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Supplier *</label>
              <select
                {...register('vendor_id', { valueAsNumber: true })}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              >
                <option value="">Select supplier...</option>
                {vendorList.map((v) => (
                  <option key={v.id} value={v.id}>
                    {v.name} ({v.code})
                  </option>
                ))}
              </select>
              {errors.vendor_id && <p className="mt-1 text-xs text-red-600">{errors.vendor_id.message}</p>}
            </div>
          )}

          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Quotation Number *</label>
              <input
                type="text"
                {...register('quotation_number')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.quotation_number && <p className="mt-1 text-xs text-red-600">{errors.quotation_number.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">RFQ / PR Reference</label>
              <input
                type="text"
                {...register('rfq_number')}
                placeholder="e.g. RFQ-2026-089"
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Status</label>
              <select
                {...register('status')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              >
                <option value="received">Received</option>
                <option value="under_review">Under Review</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Quotation Date *</label>
              <input
                type="date"
                {...register('quotation_date')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Validity Expiration Date *</label>
              <input
                type="date"
                {...register('validity_date')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>
          </div>

          {/* Line items section */}
          <div className="border-t border-slate-100 pt-4 dark:border-slate-800">
            <div className="flex items-center justify-between pb-2">
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Quoted Line Items</label>
              <Button type="button" variant="secondary" size="sm" onClick={() => append({ description: '', quantity: 1, unit_price: 0 })}>
                + Add Item
              </Button>
            </div>

            <div className="space-y-3">
              {fields.map((field, index) => (
                <div key={field.id} className="grid grid-cols-12 gap-2 items-start rounded-lg bg-slate-50 p-3 dark:bg-slate-800/40">
                  <div className="col-span-6">
                    <input
                      type="text"
                      {...register(`items.${index}.description` as const)}
                      placeholder="Item Description / SKU details"
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    />
                    {errors.items?.[index]?.description && (
                      <p className="mt-1 text-xs text-red-600">{errors.items[index]?.description?.message}</p>
                    )}
                  </div>

                  <div className="col-span-2">
                    <input
                      type="number"
                      step="0.01"
                      {...register(`items.${index}.quantity` as const, { valueAsNumber: true })}
                      placeholder="Qty"
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-center focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    />
                  </div>

                  <div className="col-span-3">
                    <input
                      type="number"
                      step="0.01"
                      {...register(`items.${index}.unit_price` as const, { valueAsNumber: true })}
                      placeholder="Unit Price"
                      className="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-right focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    />
                  </div>

                  <div className="col-span-1 flex justify-center pt-1">
                    {fields.length > 1 && (
                      <button type="button" onClick={() => remove(index)} className="text-red-500 hover:text-red-700 text-base font-bold">
                        ✕
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-4 flex justify-end items-center gap-4 bg-slate-50 p-3 rounded-lg dark:bg-slate-800/60">
              <span className="text-sm font-semibold text-slate-700 dark:text-slate-300">Total Quoted Amount:</span>
              <span className="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                PKR {calculatedTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </span>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Quotation Notes & Exceptions</label>
            <textarea
              rows={2}
              {...register('notes')}
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <Button type="button" variant="secondary" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || createMutation.isPending}>
              {createMutation.isPending ? 'Saving...' : 'Save Quotation'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
