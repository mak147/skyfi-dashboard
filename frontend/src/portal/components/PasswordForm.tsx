import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';

import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { changePassword } from '../api/portalApi';
import { passwordSchema } from '../schemas';
import type { PortalPasswordForm } from '../types';

export const PasswordForm = () => {
  const form = useForm<PortalPasswordForm>({
    resolver: zodResolver(passwordSchema),
    defaultValues: { current_password: '', new_password: '', confirm_password: '' },
  });

  const mutation = useMutation({
    mutationFn: changePassword,
    onSuccess: () => form.reset(),
  });

  const onSubmit = (values: PortalPasswordForm) => {
    mutation.mutate(values);
  };

  const submitError = mutation.error ? apiErrorMessage(mutation.error, 'Unable to change password.') : null;

  return (
    <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
      {mutation.isSuccess ? (
        <Alert title="Password updated">Your password has been changed successfully.</Alert>
      ) : null}
      {submitError ? <Alert title="Update failed">{submitError}</Alert> : null}

      <FormField control={form.control} name="current_password" label="Current password" type="password" />
      <FormField control={form.control} name="new_password" label="New password" type="password" />
      <FormField control={form.control} name="confirm_password" label="Confirm new password" type="password" />

      <div className="flex justify-end">
        <Button type="submit" isLoading={mutation.isPending}>
          Change password
        </Button>
      </div>
    </form>
  );
};
