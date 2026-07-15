import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { vendorContactSchema } from '../schemas';
import { useCreateVendorContact, useUpdateVendorContact, useVendors } from '../api/useVendors';
import type { VendorContact, VendorContactFormValues } from '../types';

interface ContactFormProps {
  vendorId?: number;
  initialData?: VendorContact | null;
  isOpen: boolean;
  onClose: () => void;
}

export const ContactForm: React.FC<ContactFormProps> = ({ vendorId, initialData, isOpen, onClose }) => {
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const createMutation = useCreateVendorContact();
  const updateMutation = useUpdateVendorContact();
  const { data: vendorsData } = useVendors({ per_page: 100 });
  const vendorList = vendorsData?.data.map((v) => v.attributes) || [];

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<VendorContactFormValues>({
    resolver: zodResolver(vendorContactSchema),
    defaultValues: {
      vendor_id: vendorId || initialData?.vendor_id || undefined,
      first_name: initialData?.first_name || '',
      last_name: initialData?.last_name || '',
      email: initialData?.email || '',
      phone: initialData?.phone || '',
      department: initialData?.department || '',
      position: initialData?.position || '',
      is_primary: Boolean(initialData?.is_primary),
      is_emergency: Boolean(initialData?.is_emergency),
      notes: initialData?.notes || '',
    },
  });

  useEffect(() => {
    if (isOpen) {
      reset({
        vendor_id: vendorId || initialData?.vendor_id || undefined,
        first_name: initialData?.first_name || '',
        last_name: initialData?.last_name || '',
        email: initialData?.email || '',
        phone: initialData?.phone || '',
        department: initialData?.department || '',
        position: initialData?.position || '',
        is_primary: Boolean(initialData?.is_primary),
        is_emergency: Boolean(initialData?.is_emergency),
        notes: initialData?.notes || '',
      });
      setErrorMsg(null);
    }
  }, [isOpen, initialData, vendorId, reset]);

  if (!isOpen) return null;

  const onSubmit = async (data: VendorContactFormValues) => {
    setErrorMsg(null);
    const targetVendorId = Number(data.vendor_id || vendorId || initialData?.vendor_id);
    if (!targetVendorId) {
      setErrorMsg('Please select a supplier for this contact.');
      return;
    }

    try {
      if (initialData?.id) {
        await updateMutation.mutateAsync({ contactId: initialData.id, data: { ...data, vendor_id: targetVendorId } });
      } else {
        await createMutation.mutateAsync({ vendorId: targetVendorId, data: { ...data, vendor_id: targetVendorId } });
      }
      reset();
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Failed to save contact record.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
      <div className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
          <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100">
            {initialData ? 'Edit Supplier Contact' : 'Add New Contact'}
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
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Supplier</label>
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
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">First Name *</label>
              <input
                type="text"
                {...register('first_name')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.first_name && <p className="mt-1 text-xs text-red-600">{errors.first_name.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Last Name *</label>
              <input
                type="text"
                {...register('last_name')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.last_name && <p className="mt-1 text-xs text-red-600">{errors.last_name.message}</p>}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Email</label>
              <input
                type="email"
                {...register('email')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Phone</label>
              <input
                type="text"
                {...register('phone')}
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Department</label>
              <input
                type="text"
                {...register('department')}
                placeholder="e.g. Technical Sales, Accounts"
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Position / Title</label>
              <input
                type="text"
                {...register('position')}
                placeholder="e.g. Account Executive"
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>
          </div>

          <div className="flex items-center gap-6 pt-2">
            <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
              <input type="checkbox" {...register('is_primary')} className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              Primary Contact
            </label>

            <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
              <input type="checkbox" {...register('is_emergency')} className="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500" />
              Emergency Contact
            </label>
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Notes</label>
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
            <Button type="submit" disabled={isSubmitting || createMutation.isPending || updateMutation.isPending}>
              {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save Contact'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
