import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '@/components/ui/button';
import { vendorSchema } from '../schemas';
import { useCreateVendor, useUpdateVendor } from '../api/useVendors';
import type { Vendor, VendorFormValues } from '../types';

interface SupplierFormProps {
  initialData?: Vendor | null;
  isOpen: boolean;
  onClose: () => void;
}

export const SupplierForm: React.FC<SupplierFormProps> = ({ initialData, isOpen, onClose }) => {
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const createMutation = useCreateVendor();
  const updateMutation = useUpdateVendor();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<VendorFormValues>({
    resolver: zodResolver(vendorSchema),
    defaultValues: {
      code: initialData?.code || `SUP-${Math.floor(1000 + Math.random() * 9000)}`,
      name: initialData?.name || '',
      status: initialData?.status || 'active',
      contact_name: initialData?.contact_name || '',
      email: initialData?.email || '',
      phone: initialData?.phone || '',
      website: initialData?.website || '',
      tax_id: initialData?.tax_id || '',
      registration_number: initialData?.registration_number || '',
      address: initialData?.address || '',
      city: initialData?.city || 'Karachi',
      country: initialData?.country || 'Pakistan',
      payment_terms: initialData?.payment_terms || 'Net 30',
      currency: initialData?.currency || 'PKR',
      category: initialData?.category || 'hardware',
      notes: initialData?.notes || '',
    },
  });

  useEffect(() => {
    if (isOpen) {
      reset({
        code: initialData?.code || `SUP-${Math.floor(1000 + Math.random() * 9000)}`,
        name: initialData?.name || '',
        status: initialData?.status || 'active',
        contact_name: initialData?.contact_name || '',
        email: initialData?.email || '',
        phone: initialData?.phone || '',
        website: initialData?.website || '',
        tax_id: initialData?.tax_id || '',
        registration_number: initialData?.registration_number || '',
        address: initialData?.address || '',
        city: initialData?.city || 'Karachi',
        country: initialData?.country || 'Pakistan',
        payment_terms: initialData?.payment_terms || 'Net 30',
        currency: initialData?.currency || 'PKR',
        category: initialData?.category || 'hardware',
        notes: initialData?.notes || '',
      });
      setErrorMsg(null);
    }
  }, [isOpen, initialData, reset]);

  if (!isOpen) return null;

  const onSubmit = async (data: VendorFormValues) => {
    setErrorMsg(null);
    try {
      if (initialData?.id) {
        await updateMutation.mutateAsync({ id: initialData.id, data });
      } else {
        await createMutation.mutateAsync(data);
      }
      reset();
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Failed to save supplier profile.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
      <div className="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
          <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100">
            {initialData ? 'Edit Supplier Profile' : 'Register New Supplier'}
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
          {/* Section 1: Basic Information */}
          <div className="border-b border-slate-100 pb-4 dark:border-slate-800">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">1. Company & Classification</h4>
            <div className="mt-3 grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Company Name *</label>
                <input
                  type="text"
                  {...register('name')}
                  placeholder="e.g. MikroTik Pak / Streakwave"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
                {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name.message}</p>}
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Supplier Code *</label>
                <input
                  type="text"
                  {...register('code')}
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
                {errors.code && <p className="mt-1 text-xs text-red-600">{errors.code.message}</p>}
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Status</label>
                <select
                  {...register('status')}
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="active">Active</option>
                  <option value="on_hold">On Hold</option>
                  <option value="inactive">Archived / Inactive</option>
                </select>
              </div>
            </div>

            <div className="mt-4 grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Category *</label>
                <select
                  {...register('category')}
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="hardware">Hardware & Equipment</option>
                  <option value="fiber_optics">Fiber & Infrastructure</option>
                  <option value="software">Software & Licensing</option>
                  <option value="contractor">Installation Contractor</option>
                  <option value="bandwidth">Bandwidth & Transit Provider</option>
                  <option value="general">General Supplies</option>
                </select>
                {errors.category && <p className="mt-1 text-xs text-red-600">{errors.category.message}</p>}
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Tax Number (NTN/STRN)</label>
                <input
                  type="text"
                  {...register('tax_id')}
                  placeholder="e.g. 1234567-8"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Registration Number</label>
                <input
                  type="text"
                  {...register('registration_number')}
                  placeholder="e.g. SECP/2026/099"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>
            </div>
          </div>

          {/* Section 2: Contact & Address */}
          <div className="border-b border-slate-100 pb-4 dark:border-slate-800">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">2. Contact Details & Address</h4>
            <div className="mt-3 grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Primary Contact Person</label>
                <input
                  type="text"
                  {...register('contact_name')}
                  placeholder="e.g. Ahmed Raza"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Email</label>
                <input
                  type="email"
                  {...register('email')}
                  placeholder="sales@supplier.com"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
                {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>}
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Phone / Cell</label>
                <input
                  type="text"
                  {...register('phone')}
                  placeholder="+92 21 31234567"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>
            </div>

            <div className="mt-4 grid grid-cols-3 gap-4">
              <div className="col-span-1">
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Website</label>
                <input
                  type="text"
                  {...register('website')}
                  placeholder="https://supplier.com"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>

              <div className="col-span-2">
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Address</label>
                <input
                  type="text"
                  {...register('address')}
                  placeholder="Suite 402, Trade Center, I.I. Chundrigar Road"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>
            </div>

            <div className="mt-4 grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">City</label>
                <input
                  type="text"
                  {...register('city')}
                  placeholder="Karachi"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Country</label>
                <input
                  type="text"
                  {...register('country')}
                  placeholder="Pakistan"
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
              </div>
            </div>
          </div>

          {/* Section 3: Financial & Purchasing Terms */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">3. Financial Reference & Terms</h4>
            <div className="mt-3 grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Default Payment Terms</label>
                <select
                  {...register('payment_terms')}
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="Prepaid / Cash on Delivery">Prepaid / Cash on Delivery</option>
                  <option value="Net 15">Net 15 Days</option>
                  <option value="Net 30">Net 30 Days</option>
                  <option value="Net 60">Net 60 Days</option>
                  <option value="50% Advance / 50% Delivery">50% Advance / 50% Delivery</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Currency</label>
                <select
                  {...register('currency')}
                  className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option value="PKR">PKR (Pakistani Rupee)</option>
                  <option value="USD">USD (US Dollar)</option>
                  <option value="EUR">EUR (Euro)</option>
                  <option value="CNY">CNY (Chinese Yuan)</option>
                </select>
              </div>
            </div>

            <div className="mt-4">
              <label className="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Internal Notes & Remarks</label>
              <textarea
                rows={2}
                {...register('notes')}
                placeholder="Preferred ordering instructions, bank account numbers, or warranty terms..."
                className="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <Button type="button" variant="secondary" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || createMutation.isPending || updateMutation.isPending}>
              {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save Supplier'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
