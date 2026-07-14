import { zodResolver } from '@hookform/resolvers/zod';
import { useQuery } from '@tanstack/react-query';
import { Controller, useForm, type Resolver } from 'react-hook-form';

import { FormField } from '@/components/common/form-field';
import { Button } from '@/components/ui/button';
import { getConnections } from '@/features/connections/api/connectionApi';
import { getCustomers } from '@/features/customers/api/customerApi';
import { getPackages } from '@/features/packages/api/packageApi';

import { editPppoeFormSchema, pppoeFormSchema, type AnyPppoeFormValues } from '../schemas';
import type { PppoeAccount } from '../types';
import { ProfileSelector } from './ProfileSelector';
import { RouterSelector } from './RouterSelector';

interface PPPoEFormProps {
  initialAccount?: PppoeAccount;
  isSubmitting: boolean;
  onSubmit: (data: AnyPppoeFormValues) => void;
  onCancel: () => void;
}

interface UnifiedFormValues {
  username: string;
  password?: string;
  customer_id: number;
  connection_id: number;
  package_id: number;
  router_id: number;
  profile: string;
  service: string;
  ip_pool?: string;
  static_ip?: string;
  mac_binding?: string;
  caller_id?: string;
  rate_limit?: string;
  session_timeout?: number;
  idle_timeout?: number;
  shared_users: number;
  status: 'active' | 'disabled' | 'suspended' | 'pending' | 'error';
  notes?: string;
}

export const PPPoEForm = ({ initialAccount, isSubmitting, onSubmit, onCancel }: PPPoEFormProps) => {
  const isEditing = Boolean(initialAccount);
  const schema = isEditing ? editPppoeFormSchema : pppoeFormSchema;

  const customersQuery = useQuery({
    queryKey: ['customers', 'selector'],
    queryFn: () => getCustomers(1, 100, {}, 'full_name'),
  });

  const connectionsQuery = useQuery({
    queryKey: ['connections', 'selector'],
    queryFn: () => getConnections(1, 100, {}, 'connection_number'),
  });

  const packagesQuery = useQuery({
    queryKey: ['packages', 'selector'],
    queryFn: () => getPackages(1, 100, {}, 'name'),
  });

  const form = useForm<UnifiedFormValues>({
    resolver: zodResolver(schema) as unknown as Resolver<UnifiedFormValues>,
    mode: 'onTouched',
    defaultValues: {
      username: initialAccount?.username ?? '',
      password: '',
      customer_id: initialAccount?.customer_id ?? 0,
      connection_id: initialAccount?.connection_id ?? 0,
      package_id: initialAccount?.package_id ?? 0,
      router_id: initialAccount?.router_id ?? 0,
      profile: initialAccount?.profile ?? '',
      service: initialAccount?.service ?? 'pppoe',
      ip_pool: initialAccount?.ip_pool ?? '',
      static_ip: initialAccount?.static_ip ?? '',
      mac_binding: initialAccount?.mac_binding ?? '',
      caller_id: initialAccount?.caller_id ?? '',
      rate_limit: initialAccount?.rate_limit ?? '',
      session_timeout: initialAccount?.session_timeout ?? undefined,
      idle_timeout: initialAccount?.idle_timeout ?? undefined,
      shared_users: initialAccount?.shared_users ?? 1,
      status: initialAccount?.status ?? 'active',
      notes: initialAccount?.notes ?? '',
    },
  });

  const selectedRouterId = Number(form.watch('router_id'));

  const customers = customersQuery.data?.data.map((item) => item.attributes) ?? [];
  const connections = connectionsQuery.data?.data.map((item) => item.attributes) ?? [];
  const packages = packagesQuery.data?.data.map((item) => item.attributes) ?? [];

  return (
    <form className="space-y-8" onSubmit={form.handleSubmit((data) => onSubmit(data as unknown as AnyPppoeFormValues))}>
      <fieldset>
        <legend className="text-base font-semibold text-slate-900">PPPoE Account Identity</legend>
        <p className="mt-1 text-sm text-slate-500">
          Set the unique login credentials and link this secret to a customer and internet service connection.
        </p>
        <div className="mt-5 grid gap-5 md:grid-cols-2">
          <FormField control={form.control} name="username" label="Username" placeholder="cust101-pppoe" />
          <FormField
            control={form.control}
            name="password"
            label={isEditing ? 'New Password (leave blank to keep current)' : 'Password'}
            type="password"
            placeholder={isEditing ? '••••••••' : 'Enter strong password'}
          />

          <Controller
            control={form.control}
            name="customer_id"
            render={({ field, fieldState }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Customer Account</label>
                <select
                  disabled={isEditing}
                  value={field.value || ''}
                  onChange={(e) => field.onChange(Number(e.target.value))}
                  className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-slate-50"
                >
                  <option value="">Select Customer...</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.full_name} ({c.customer_code})
                    </option>
                  ))}
                </select>
                {fieldState.error ? <p className="mt-1 text-xs text-red-600">{fieldState.error.message}</p> : null}
              </div>
            )}
          />

          <Controller
            control={form.control}
            name="connection_id"
            render={({ field, fieldState }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Connection ID</label>
                <select
                  disabled={isEditing}
                  value={field.value || ''}
                  onChange={(e) => field.onChange(Number(e.target.value))}
                  className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-slate-50"
                >
                  <option value="">Select Connection...</option>
                  {connections.map((conn) => (
                    <option key={conn.id} value={conn.id}>
                      {conn.connection_number} ({conn.name})
                    </option>
                  ))}
                </select>
                {fieldState.error ? <p className="mt-1 text-xs text-red-600">{fieldState.error.message}</p> : null}
              </div>
            )}
          />
        </div>
      </fieldset>

      <fieldset className="border-t border-slate-200 pt-7">
        <legend className="text-base font-semibold text-slate-900">MikroTik & Package Configuration</legend>
        <p className="mt-1 text-sm text-slate-500">
          Assign the target MikroTik router, service package, and corresponding RouterOS profile.
        </p>
        <div className="mt-5 grid gap-5 md:grid-cols-2">
          <Controller
            control={form.control}
            name="router_id"
            render={({ field, fieldState }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Target MikroTik Router</label>
                <div className="mt-2">
                  <RouterSelector value={field.value || ''} onChange={field.onChange} disabled={isEditing && initialAccount?.sync_status === 'synced'} />
                </div>
                {fieldState.error ? <p className="mt-1 text-xs text-red-600">{fieldState.error.message}</p> : null}
              </div>
            )}
          />

          <Controller
            control={form.control}
            name="package_id"
            render={({ field, fieldState }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Internet Package</label>
                <select
                  value={field.value || ''}
                  onChange={(e) => field.onChange(Number(e.target.value))}
                  className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                  <option value="">Select Package...</option>
                  {packages.map((pkg) => {
                    const dl = pkg.bandwidth?.download_kbps ? Math.round(pkg.bandwidth.download_kbps / 1024) : 0;
                    const ul = pkg.bandwidth?.upload_kbps ? Math.round(pkg.bandwidth.upload_kbps / 1024) : 0;
                    return (
                      <option key={pkg.id} value={pkg.id}>
                        {pkg.name} ({dl}M / {ul}M)
                      </option>
                    );
                  })}
                </select>
                {fieldState.error ? <p className="mt-1 text-xs text-red-600">{fieldState.error.message}</p> : null}
              </div>
            )}
          />

          <Controller
            control={form.control}
            name="profile"
            render={({ field, fieldState }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">RouterOS PPPoE Profile</label>
                <div className="mt-2">
                  <ProfileSelector routerId={selectedRouterId} value={field.value || ''} onChange={field.onChange} />
                </div>
                {fieldState.error ? <p className="mt-1 text-xs text-red-600">{fieldState.error.message}</p> : null}
              </div>
            )}
          />

          <Controller
            control={form.control}
            name="status"
            render={({ field }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Account Status</label>
                <select
                  value={field.value}
                  onChange={(e) => field.onChange(e.target.value as UnifiedFormValues['status'])}
                  className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                  <option value="active">Active (Enabled on Router)</option>
                  <option value="disabled">Disabled (Secret disabled)</option>
                  <option value="suspended">Suspended (Service hold)</option>
                  <option value="pending">Pending (Scheduled)</option>
                </select>
              </div>
            )}
          />
        </div>
      </fieldset>

      <fieldset className="border-t border-slate-200 pt-7">
        <legend className="text-base font-semibold text-slate-900">Advanced Network Bindings & Limits</legend>
        <p className="mt-1 text-sm text-slate-500">
          Configure static IP assignment, MAC address binding, custom rate limits, and timeout parameters.
        </p>
        <div className="mt-5 grid gap-5 md:grid-cols-3">
          <FormField control={form.control} name="static_ip" label="Static IP (Remote Address)" placeholder="100.64.1.50" />
          <FormField control={form.control} name="mac_binding" label="MAC Binding (Caller ID)" placeholder="AA:BB:CC:DD:EE:FF" />
          <FormField control={form.control} name="caller_id" label="Additional Caller ID / Tag" placeholder="VLAN101-CPE" />
          <FormField control={form.control} name="rate_limit" label="Custom Rate Limit" placeholder="20M/100M (Overrides queue)" />
          <Controller
            control={form.control}
            name="session_timeout"
            render={({ field }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Session Timeout (seconds)</label>
                <input
                  type="number"
                  placeholder="86400 (24h)"
                  value={field.value ?? ''}
                  onChange={(e) => field.onChange(e.target.value ? Number(e.target.value) : undefined)}
                  className="mt-2 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>
            )}
          />
          <Controller
            control={form.control}
            name="idle_timeout"
            render={({ field }) => (
              <div>
                <label className="text-sm font-medium text-slate-700">Idle Timeout (seconds)</label>
                <input
                  type="number"
                  placeholder="1800 (30m)"
                  value={field.value ?? ''}
                  onChange={(e) => field.onChange(e.target.value ? Number(e.target.value) : undefined)}
                  className="mt-2 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                />
              </div>
            )}
          />
        </div>
      </fieldset>

      <fieldset className="border-t border-slate-200 pt-7">
        <legend className="text-base font-semibold text-slate-900">Notes & Comments</legend>
        <div className="mt-4">
          <textarea
            {...form.register('notes')}
            placeholder="Operational notes, CPE installation comments, or VIP flag..."
            className="h-24 w-full rounded-md border border-slate-300 p-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
      </fieldset>

      <div className="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isSubmitting}>
          Cancel
        </Button>
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving...' : isEditing ? 'Save Changes' : 'Create PPPoE User'}
        </Button>
      </div>
    </form>
  );
};
