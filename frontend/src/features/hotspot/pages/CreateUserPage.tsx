import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useCreateHotspotUser } from '../api/useHotspot';
import { hotspotUserFormSchema, type HotspotUserFormValues } from '../schemas';

export const CreateUserPage = () => {
  const navigate = useNavigate();
  const createMutation = useCreateHotspotUser();

  const form = useForm<HotspotUserFormValues>({
    resolver: zodResolver(hotspotUserFormSchema),
    defaultValues: {
      username: '',
      password: '',
      router_id: 0,
      profile_name: 'default',
      profile_id: 0,
      customer_id: 0,
      status: 'active',
      notes: '',
    },
  });

  const onSubmit = (data: HotspotUserFormValues) => {
    createMutation.mutate(data, {
      onSuccess: (user) => navigate(`/hotspot/users/${user.id}`),
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Create Hotspot User</h1>
          <p className="mt-1 text-sm text-slate-500">Add a new hotspot user that will be provisioned on the MikroTik router.</p>
        </div>
        <Button variant="secondary" onClick={() => navigate('/hotspot')}>Cancel</Button>
      </div>

      {createMutation.error ? (
        <Alert title="Failed to create user" variant="danger">{apiErrorMessage(createMutation.error)}</Alert>
      ) : null}

      <form onSubmit={form.handleSubmit(onSubmit)} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="block text-sm font-semibold text-slate-700">Username *</label>
            <input {...form.register('username')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            {form.formState.errors.username ? <p className="mt-1 text-xs text-red-600">{form.formState.errors.username.message}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Password *</label>
            <input type="password" {...form.register('password')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            {form.formState.errors.password ? <p className="mt-1 text-xs text-red-600">{form.formState.errors.password.message}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Router ID *</label>
            <input type="number" {...form.register('router_id', { valueAsNumber: true })} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            {form.formState.errors.router_id ? <p className="mt-1 text-xs text-red-600">{form.formState.errors.router_id.message}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Profile Name *</label>
            <input {...form.register('profile_name')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            {form.formState.errors.profile_name ? <p className="mt-1 text-xs text-red-600">{form.formState.errors.profile_name.message}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Customer ID (optional)</label>
            <input type="number" {...form.register('customer_id', { valueAsNumber: true })} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Uptime Limit (e.g. 1d, 8h)</label>
            <input {...form.register('limit_uptime')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Data Limit (bytes)</label>
            <input type="number" {...form.register('limit_bytes_total', { valueAsNumber: true })} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">MAC Address</label>
            <input {...form.register('mac_address')} placeholder="XX:XX:XX:XX:XX:XX" className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Status</label>
            <select {...form.register('status')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
              <option value="active">Active</option>
              <option value="disabled">Disabled</option>
              <option value="pending">Pending</option>
            </select>
          </div>
        </div>

        <div>
          <label className="block text-sm font-semibold text-slate-700">Notes</label>
          <textarea {...form.register('notes')} rows={3} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
        </div>

        <div className="flex justify-end gap-2 pt-4 border-t border-slate-200">
          <Button variant="secondary" type="button" onClick={() => navigate('/hotspot')}>Cancel</Button>
          <Button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating...' : 'Create User'}
          </Button>
        </div>
      </form>
    </div>
  );
};
