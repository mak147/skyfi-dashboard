import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { apiErrorMessage } from '@/lib/apiClient';

import { forgotPassword } from '../api/portalApi';
import { forgotPasswordSchema } from '../schemas';
import type { z } from 'zod';

type ForgotPasswordValues = z.infer<typeof forgotPasswordSchema>;

export const ForgotPasswordPage = () => {
  const form = useForm<ForgotPasswordValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  });

  const mutation = useMutation({
    mutationFn: (values: ForgotPasswordValues) => forgotPassword(values.email),
  });

  const onSubmit = (values: ForgotPasswordValues) => {
    mutation.mutate(values);
  };

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
      <motion.section
        animate={{ opacity: 1, y: 0 }}
        className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-card sm:p-8"
        initial={{ opacity: 0, y: 10 }}
        transition={{ duration: 0.3 }}
        aria-labelledby="portal-forgot-title"
      >
        <div className="mb-8">
          <div className="mb-6 flex items-center gap-3" aria-label="SkyFi Networks">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-bold text-white">S</span>
            <span className="text-lg font-semibold tracking-tight text-slate-900">SkyFi Networks</span>
          </div>
          <h1 id="portal-forgot-title" className="text-2xl font-bold leading-tight text-slate-800 sm:text-3xl">
            Reset your password
          </h1>
          <p className="mt-2 text-sm leading-6 text-slate-500">
            Enter your email address and we will send you a password reset link.
          </p>
        </div>

        {mutation.isSuccess ? (
          <Alert title="Check your email" variant="success">
            If an account exists with that address, you will receive reset instructions shortly.
          </Alert>
        ) : (
          <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)} noValidate>
            {mutation.error ? (
              <Alert title="Request failed">{apiErrorMessage(mutation.error, 'Unable to send reset link.')}</Alert>
            ) : null}

            <FormField
              control={form.control}
              name="email"
              label="Email address"
              type="email"
              placeholder="you@example.com"
              autoComplete="email"
            />

            <Button className="w-full" type="submit" isLoading={mutation.isPending}>
              Send reset link
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
