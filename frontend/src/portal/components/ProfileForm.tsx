import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';

import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { updateProfile } from '../api/portalApi';
import { profileSchema } from '../schemas';
import type { PortalCustomer, PortalProfileForm } from '../types';

interface ProfileFormProps {
  customer: PortalCustomer;
}

export const ProfileForm = ({ customer }: ProfileFormProps) => {
  const queryClient = useQueryClient();
  const form = useForm<PortalProfileForm>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      full_name: customer.full_name ?? '',
      phone: customer.phone ?? '',
      whatsapp: customer.whatsapp ?? '',
      email: customer.email ?? '',
      address: customer.address ?? '',
      city: customer.city ?? '',
      area: customer.area ?? '',
      emergency_contact_name: customer.emergency_contact_name ?? '',
      emergency_contact_phone: customer.emergency_contact_phone ?? '',
    },
  });

  const mutation = useMutation({
    mutationFn: updateProfile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['portal', 'profile'] });
      queryClient.invalidateQueries({ queryKey: ['portal', 'dashboard'] });
    },
  });

  const onSubmit = (values: PortalProfileForm) => {
    mutation.mutate(values);
  };

  const submitError = mutation.error ? apiErrorMessage(mutation.error, 'Unable to update profile.') : null;

  return (
    <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
      {submitError ? <Alert title="Update failed">{submitError}</Alert> : null}

      <div className="grid gap-6 sm:grid-cols-2">
        <FormField control={form.control} name="full_name" label="Full name" type="text" />
        <FormField control={form.control} name="email" label="Email address" type="email" />
        <FormField control={form.control} name="phone" label="Phone number" type="tel" />
        <FormField control={form.control} name="whatsapp" label="WhatsApp number" type="tel" />
        <FormField control={form.control} name="address" label="Address" type="text" />
        <FormField control={form.control} name="city" label="City" type="text" />
        <FormField control={form.control} name="area" label="Area" type="text" />
        <FormField control={form.control} name="emergency_contact_name" label="Emergency contact name" type="text" />
        <FormField control={form.control} name="emergency_contact_phone" label="Emergency contact phone" type="tel" />
      </div>

      <div className="flex justify-end">
        <Button type="submit" isLoading={mutation.isPending}>
          Save changes
        </Button>
      </div>
    </form>
  );
};
