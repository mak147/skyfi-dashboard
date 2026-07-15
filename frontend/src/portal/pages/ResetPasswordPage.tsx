import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { motion } from 'framer-motion';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { apiErrorMessage } from '@/lib/apiClient';

import { resetPassword } from '../api/portalApi';
import { resetPasswordSchema } from '../schemas';
import type { z } from 'zod';

type ResetPasswordValues = z.infer<typeof resetPasswordSchema>;

export const ResetPasswordPage = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';

  const form = useForm<ResetPasswordValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: { token, password: '', confirm_password: '' },
  });

  const mutation = useMutation({
    mutationFn: (values: ResetPasswordValues) => resetPassword(values.token, values.password),
    onSuccess: () => {
      setTimeout(() => navigate('/portal/login', { replace: true }), 1500);
    },
  });

  const onSubmit = (values: ResetPasswordValues) => {
    mutation.mutate(values);
  };

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
      <motion.section
        animate={{ opacity: 1, y: 0 }}
        className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-card sm:p-8"
        initial={{ opacity: 0, y: 10 }}
        transition={{ duration: 0.3 }}
        aria-labelledby="portal-reset-title"
      >
        <div className="mb-8">
          <div className="mb-6 flex items-center gap-3" aria-label="SkyFi Networks">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-bold text-white">S</span>
            <span className="text-lg font-semibold tracking-tight text-slate-900">SkyFi Networks</span>
          </div>
          <h1 id="portal-reset-title" className="text-2xl font-bold leading-tight text-slate-800 sm:text-3xl">
            Choose a new password
          </h1>
          <p className="mt-2 text-sm leading-6 text-slate-500">
            Enter your new password below to regain access to your account.
          </p>
        </div>

        {!token ? (
          <Alert title="Invalid reset link">The reset link is missing or expired.</Alert>
        ) : mutation.isSuccess ? (
          <Alert title="Password reset" variant="success">
            Your password has been updated. Redirecting to sign in...
          </Alert>
        ) : (
          <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)} noValidate>
            {mutation.error ? (
              <Alert title="Reset failed">{apiErrorMessage(mutation.error, 'Unable to reset password.')}</Alert>
            ) : null}

            <FormField
              control={form.control}
              name="password"
              label="New password"
              type="password"
              placeholder="Enter a new password"
              autoComplete="new-password"
            />
            <FormField
              control={form.control}
              name="confirm_password"
              label="Confirm new password"
              type="password"
              placeholder="Confirm your new password"
              autoComplete="new-password"
            />

            <Button className="w-full" type="submit" isLoading={mutation.isPending}>
              Reset password
            </Button>
          </form>
        )}

        <div className="mt-6 text-center">
          <Link
            to="/portal/login"
            className="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
          >
            Back to sign in
          </Link>
        </div>
      </motion.section>
    </main>
  );
};
