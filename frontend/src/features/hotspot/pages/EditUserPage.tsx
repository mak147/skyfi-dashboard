import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect } from 'react';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useHotspotUser, useUpdateHotspotUser } from '../api/useHotspot';
import { editHotspotUserFormSchema, type EditHotspotUserFormValues } from '../schemas';

export const EditUserPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const userId = Number(id ?? '0');

  const { data: user, isLoading } = useHotspotUser(userId);
  const updateMutation = useUpdateHotspotUser();

  const form = useForm<EditHotspotUserFormValues>({
    resolver: zodResolver(editHotspotUserFormSchema),
    defaultValues: { username: '', password: '', router_id: 0, profile_name: 'default' },
  });

  useEffect(() => {
    if (user) {
      form.reset({
        username: user.username,
        password: '',
        router_id: user.router_id,
        profile_name: user.profile_name,
        profile_id: user.profile_id ?? 0,
        customer_id: user.customer_id ?? 0,
        limit_uptime: user.limit_uptime ?? '',
        limit_bytes_total: user.limit_bytes_total ?? 0,
        mac_address: user.mac_address ?? '',
        status: user.status as 'active' | 'disabled' | 'suspended' | 'pending',
        notes: user.notes ?? '',
      });
    }
  }, [user, form]);

  const onSubmit = (data: EditHotspotUserFormValues) => {
    updateMutation.mutate({ id: userId, data }, {
      onSuccess: () => navigate(`/hotspot/users/${userId}`),
    });
  };

  if (isLoading) {
    return <div className="h-64 animate-pulse rounded-xl bg-slate-100" />;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Edit Hotspot User</h1>
          <p className="mt-1 text-sm text-slate-500">Update {user?.username} — changes are pushed to MikroTik router.</p>
        </div>
        <Button variant="secondary" onClick={() => navigate(`/hotspot/users/${userId}`)}>Cancel</Button>
      </div>

      {updateMutation.error ? (
        <Alert title="Failed to update user" variant="danger">{apiErrorMessage(updateMutation.error)}</Alert>
      ) : null}

      <form onSubmit={form.handleSubmit(onSubmit)} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="block text-sm font-semibold text-slate-700">Username</label>
            <input {...form.register('username')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
            {form.formState.errors.username ? <p className="mt-1 text-xs text-red-600">{form.formState.errors.username.message}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">New Password (leave blank to keep)</label>
            <input type="password" {...form.register('password')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Router ID</label>
            <input type="number" {...form.register('router_id', { valueAsNumber: true })} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Profile Name</label>
            <input {...form.register('profile_name')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Uptime Limit</label>
            <input {...form.register('limit_uptime')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Data Limit (bytes)</label>
            <input type="number" {...form.register('limit_bytes_total', { valueAsNumber: true })} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">MAC Address</label>
            <input {...form.register('mac_address')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-700">Status</label>
            <select {...form.register('status')} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
              <option value="active">Active</option>
              <option value="disabled">Disabled</option>
              <option value="suspended">Suspended</option>
              <option value="pending">Pending</option>
            </select>
          </div>
        </div>

        <div>
          <label className="block text-sm font-semibold text-slate-700">Notes</label>
          <textarea {...form.register('notes')} rows={3} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
        </div>

        <div className="flex justify-end gap-2 pt-4 border-t border-slate-200">
          <Button variant="secondary" type="button" onClick={() => navigate(`/hotspot/users/${userId}`)}>Cancel</Button>
          <Button type="submit" disabled={updateMutation.isPending}>
            {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
          </Button>
        </div>
      </form>
    </div>
  );
};
