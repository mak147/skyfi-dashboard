import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import type { CustomerFormData } from '../types';

const customerSchema = z.object({
  full_name: z.string().min(1, 'Full name is required.'),
  father_husband_name: z.string(),
  cnic: z.string(),
  phone: z.string().min(1, 'Phone number is required.'),
  whatsapp: z.string(),
  email: z.union([z.string().email('Please enter a valid email.'), z.literal('')]),
  address: z.string().min(1, 'Address is required.'),
  city: z.string().min(1, 'City is required.'),
  area: z.string().min(1, 'Area is required.'),
  notes: z.string(),
  registration_date: z.string(),
  installation_date: z.string(),
  installation_technician_id: z.string(),
  emergency_contact_name: z.string(),
  emergency_contact_phone: z.string(),
});

type FormValues = z.infer<typeof customerSchema>;

interface CustomerFormProps {
  defaultValues?: Partial<CustomerFormData>;
  onSubmit: (data: CustomerFormData) => void;
  isSubmitting?: boolean;
  error?: string | null;
  submitLabel?: string;
}

export const CustomerForm = ({
  defaultValues,
  onSubmit,
  isSubmitting,
  error,
  submitLabel = 'Save Customer',
}: CustomerFormProps) => {
  const form = useForm<FormValues>({
    resolver: zodResolver(customerSchema),
    defaultValues: {
      full_name: '',
      father_husband_name: '',
      cnic: '',
      phone: '',
      whatsapp: '',
      email: '',
      address: '',
      city: '',
      area: '',
      notes: '',
      registration_date: '',
      installation_date: '',
      installation_technician_id: '',
      emergency_contact_name: '',
      emergency_contact_phone: '',
      ...defaultValues,
    },
  });

  useEffect(() => {
    if (defaultValues) {
      Object.entries(defaultValues).forEach(([key, value]) => {
        if (value !== undefined) {
          form.setValue(key as keyof FormValues, value as string);
        }
      });
    }
  }, [defaultValues, form]);

  const handleSubmit = (values: FormValues) => {
    onSubmit(values as CustomerFormData);
  };

  const Field = ({
    name,
    label,
    type = 'text',
    required,
  }: {
    name: keyof FormValues;
    label: string;
    type?: string;
    required?: boolean;
  }) => (
    <div>
      <Label htmlFor={name} required={required}>
        {label}
      </Label>
      <Input
        id={name}
        type={type}
        {...form.register(name)}
        isError={Boolean(form.formState.errors[name])}
        className="mt-1.5"
      />
      {form.formState.errors[name] && (
        <p className="mt-1.5 text-xs text-red-600" role="alert">
          {form.formState.errors[name]?.message}
        </p>
      )}
    </div>
  );

  return (
    <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-6">
      {error && (
        <Alert title="Error" variant="danger">
          {error}
        </Alert>
      )}

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Personal Information</h3>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <Field name="full_name" label="Full Name" required />
          <Field name="father_husband_name" label="Father / Husband Name" />
          <Field name="cnic" label="CNIC / ID Number" />
          <Field name="phone" label="Phone Number" required />
          <Field name="whatsapp" label="WhatsApp Number" />
          <Field name="email" label="Email" type="email" />
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Address</h3>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <Field name="address" label="Address" required />
          </div>
          <Field name="city" label="City" required />
          <Field name="area" label="Area / Zone" required />
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Service Information</h3>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <Field name="registration_date" label="Registration Date" type="date" />
          <Field name="installation_date" label="Installation Date" type="date" />
          <Field name="installation_technician_id" label="Installation Technician ID" type="number" />
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Emergency Contact</h3>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <Field name="emergency_contact_name" label="Emergency Contact Name" />
          <Field name="emergency_contact_phone" label="Emergency Contact Phone" />
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Notes</h3>
        <div className="mt-4">
          <textarea
            id="notes"
            rows={4}
            className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            {...form.register('notes')}
          />
        </div>
      </div>

      <div className="flex items-center justify-end gap-3">
        <Button type="submit" isLoading={isSubmitting}>
          {submitLabel}
        </Button>
      </div>
    </form>
  );
};
