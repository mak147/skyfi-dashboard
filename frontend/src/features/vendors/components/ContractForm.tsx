import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { vendorContractSchema } from '../schemas';
import { useCreateVendorContract, useUpdateVendorContract, useVendors } from '../api/useVendors';
import type { VendorContract, VendorContractFormValues } from '../types';

interface ContractFormProps {
  vendorId?: number;
  initialData?: VendorContract | null;
  isOpen: boolean;
  onClose: () => void;
}

export const ContractForm: React.FC<ContractFormProps> = ({ vendorId, initialData, isOpen, onClose }) => {
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const createMutation = useCreateVendorContract();
  const updateMutation = useUpdateVendorContract();
  const { data: vendorsData } = useVendors({ per_page: 100 });
  const vendorList = vendorsData?.data.map((v) => v.attributes) || [];

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<VendorContractFormValues>({
    resolver: zodResolver(vendorContractSchema),
    defaultValues: {
      vendor_id: vendorId || initialData?.vendor_id || undefined,
      contract_number: initialData?.contract_number || `CNT-${Math.floor(1000 + Math.random() * 9000)}`,
      title: initialData?.title || '',
      start_date: initialData?.start_date || new Date().toISOString().split('T')[0],
      end_date: initialData?.end_date || new Date(Date.now() + 31536000000).toISOString().split('T')[0],
      renewal_date: initialData?.renewal_date || '',
      contract_value: Number(initialData?.contract_value || 0),
      currency: initialData?.currency || 'PKR',
      status: initialData?.status || 'active',
      attachment_path: initialData?.attachment_path || '',
      notes: initialData?.notes || '',
    },
  });

  useEffect(() => {
    if (isOpen) {
      reset({
        vendor_id: vendorId || initialData?.vendor_id || undefined,
        contract_number: initialData?.contract_number || `CNT-${Math.floor(1000 + Math.random() * 9000)}`,
        title: initialData?.title || '',
        start_date: initialData?.start_date || new Date().toISOString().split('T')[0],
        end_date: initialData?.end_date || new Date(Date.now() + 31536000000).toISOString().split('T')[0],
        renewal_date: initialData?.renewal_date || '',
        contract_value: Number(initialData?.contract_value || 0),
        currency: initialData?.currency || 'PKR',
        status: initialData?.status || 'active',
        attachment_path: initialData?.attachment_path || '',
        notes: initialData?.notes || '',
      });
      setErrorMsg(null);
    }
  }, [isOpen, initialData, vendorId, reset]);

  if (!isOpen) return null;

  const onSubmit = async (data: VendorContractFormValues) => {
    setErrorMsg(null);
    const targetVendorId = Number(data.vendor_id || vendorId || initialData?.vendor_id);
    if (!targetVendorId) {
      setErrorMsg('Please select a supplier for this contract.');
      return;
    }

    try {
      if (initialData?.id) {
        await updateMutation.mutateAsync({ contractId: initialData.id, data: { ...data, vendor_id: targetVendorId } });
      } else {
        await createMutation.mutateAsync({ vendorId: targetVendorId, data: { ...data, vendor_id: targetVendorId } });
      }
      reset();
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Failed to save contract record.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
      <div className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
          <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100">
            {initialData ? 'Edit Supplier Contract' : 'Register New Contract'}
          </h3>
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
          {!vendorId && !initialData?.vendor_id && (
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

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Contract Number *</label>
              <input
                type="text"
                {...register('contract_number')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.contract_number && <p className="mt-1 text-xs text-red-600">{errors.contract_number.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Status *</label>
              <select
                {...register('status')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              >
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="expiring">Expiring Soon</option>
                <option value="expired">Expired</option>
                <option value="terminated">Terminated</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Contract Title *</label>
            <input
              type="text"
              {...register('title')}
              placeholder="e.g. Master Hardware Supply & SLA Agreement 2026"
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
            {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Start Date *</label>
              <input
                type="date"
                {...register('start_date')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.start_date && <p className="mt-1 text-xs text-red-600">{errors.start_date.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">End Date *</label>
              <input
                type="date"
                {...register('end_date')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.end_date && <p className="mt-1 text-xs text-red-600">{errors.end_date.message}</p>}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Renewal / Notice Date</label>
              <input
                type="date"
                {...register('renewal_date')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Total Contract Value (PKR)</label>
              <input
                type="number"
                step="0.01"
                {...register('contract_value', { valueAsNumber: true })}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.contract_value && <p className="mt-1 text-xs text-red-600">{errors.contract_value.message}</p>}
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Attachment Placeholder (Document URL/Path)</label>
            <input
              type="text"
              {...register('attachment_path')}
              placeholder="e.g. /storage/contracts/master-agreement-2026.pdf"
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Contract Terms & Notes</label>
            <textarea
              rows={2}
              {...register('notes')}
              placeholder="Key terms, warranty provisions, penalties..."
              className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <Button type="button" variant="secondary" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || createMutation.isPending || updateMutation.isPending}>
              {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save Contract'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
