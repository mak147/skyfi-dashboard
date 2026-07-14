import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/common/form-field';
import { apiErrorMessage } from '@/lib/apiClient';

import { testRouterConnection } from '../api/mikrotikApi';
import { connectionTestSchema, type ConnectionTestValues } from '../schemas';
import type { ConnectionTestResult } from '../types';

interface ConnectionTesterProps {
  defaults?: Partial<ConnectionTestValues>;
  onSuccess?: (result: ConnectionTestResult) => void;
}

export const ConnectionTester = ({ defaults, onSuccess }: ConnectionTesterProps) => {
  const form = useForm<ConnectionTestValues>({
    resolver: zodResolver(connectionTestSchema),
    mode: 'onTouched',
    defaultValues: { host: defaults?.host ?? '', api_port: defaults?.api_port ?? 8729, api_username: defaults?.api_username ?? '', api_password: '' },
  });
  const mutation = useMutation({ mutationFn: testRouterConnection, onSuccess });

  return (
    <form className="space-y-4" onSubmit={form.handleSubmit((data) => mutation.mutate(data))}>
      <div className="grid gap-4 sm:grid-cols-2"><FormField control={form.control} name="host" label="Host / IP address" placeholder="10.0.0.1" autoComplete="off" /><FormField control={form.control} name="api_username" label="API username" placeholder="skyfi-api" autoComplete="username" /></div>
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="text-sm font-medium text-slate-700">TLS API port<input type="number" {...form.register('api_port', { valueAsNumber: true })} className="mt-2 h-10 w-full rounded-md border border-slate-300 px-3 text-sm" aria-invalid={Boolean(form.formState.errors.api_port)} /></label>
        <FormField control={form.control} name="api_password" label="API password" type="password" autoComplete="current-password" />
      </div>
      {mutation.error ? <Alert title="Connection failed" variant="danger">{apiErrorMessage(mutation.error, 'The router could not be reached. Confirm TLS API access and credentials.')}</Alert> : null}
      {mutation.data ? <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status"><p className="font-semibold">Connection successful</p><p className="mt-1">{mutation.data.identity ?? 'Router'} · {mutation.data.routeros_version ?? 'Version unavailable'} · {mutation.data.latency_ms.toFixed(1)} ms</p></div> : null}
      <Button type="submit" isLoading={mutation.isPending}>Test secure connection</Button>
    </form>
  );
};
