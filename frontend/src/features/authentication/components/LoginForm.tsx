import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { useLocation, useNavigate } from 'react-router-dom';
import { z } from 'zod';

import { FormField } from '@/components/common/form-field';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { useAppDispatch } from '@/store/hooks';
import { sessionStarted } from '@/store/authSlice';

import { login } from '../api/authApi';
import type { LoginPayload } from '../types';

const loginSchema = z.object({
  email: z.string().trim().email('Please enter a valid email address.'),
  password: z.string().min(8, 'Password must be at least 8 characters long.'),
  rememberMe: z.boolean(),
});

type LoginFormValues = z.infer<typeof loginSchema>;

export const LoginForm = () => {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const location = useLocation();
  const form = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    mode: 'onTouched',
    defaultValues: { email: '', password: '', rememberMe: false },
  });
  const loginMutation = useMutation({
    mutationFn: (values: LoginPayload) => login(values),
    onSuccess: (session) => {
      dispatch(sessionStarted(session));
      const destination = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname ?? '/';
      navigate(destination, { replace: true });
    },
  });

  const onSubmit = (values: LoginFormValues) => {
    loginMutation.mutate(values);
  };

  const submitError = loginMutation.error ? apiErrorMessage(loginMutation.error, 'Unable to sign in. Check your email and password.') : null;

  return (
    <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)} noValidate>
      {submitError ? <Alert title="Sign-in failed">{submitError}</Alert> : null}

      <FormField
        control={form.control}
        name="email"
        label="Email address"
        type="email"
        placeholder="you@example.com"
        autoComplete="email"
      />
      <FormField
        control={form.control}
        name="password"
        label="Password"
        type="password"
        placeholder="Enter your password"
        autoComplete="current-password"
      />

      <label className="flex items-center gap-3 text-sm text-slate-600">
        <input
          type="checkbox"
          className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
          {...form.register('rememberMe')}
        />
        Keep me signed in for 30 days
      </label>

      <Button className="w-full" type="submit" isLoading={loginMutation.isPending}>
        Sign in
      </Button>
    </form>
  );
};
