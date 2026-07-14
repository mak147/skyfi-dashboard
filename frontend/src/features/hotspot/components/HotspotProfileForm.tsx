import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect } from 'react';

import { Button } from '@/components/ui/button';

import { hotspotProfileFormSchema, type HotspotProfileFormValues } from '../schemas';
import type { HotspotProfile } from '../types';

interface HotspotProfileFormProps {
  profile?: HotspotProfile | null;
  onSubmit: (data: HotspotProfileFormValues) => void;
  onCancel?: () => void;
  isSubmitting?: boolean;
}

export const HotspotProfileForm = ({ profile, onSubmit, onCancel, isSubmitting }: HotspotProfileFormProps) => {
  const form = useForm<HotspotProfileFormValues>({
    resolver: zodResolver(hotspotProfileFormSchema),
    defaultValues: {
      name: '',
      router_id: 0,
      router_profile_name: 'default',
      rate_limit_up: '',
      rate_limit_down: '',
      session_timeout: 0,
      idle_timeout: 0,
      shared_users: 1,
      mac_cookie_timeout: '',
      login_methods: 'http-pap',
      status: 'active',
      notes: '',
    },
  });

  useEffect(() => {
    if (profile) {
      form.reset({
        name: profile.name,
        router_id: profile.router_id,
        router_profile_name: profile.router_profile_name,
        rate_limit_up: profile.rate_limit_up ?? '',
        rate_limit_down: profile.rate_limit_down ?? '',
        session_timeout: profile.session_timeout ?? 0,
        idle_timeout: profile.idle_timeout ?? 0,
        shared_users: profile.shared_users,
        mac_cookie_timeout: profile.mac_cookie_timeout ?? '',
        login_methods: profile.login_methods,
        status: profile.status,
        notes: profile.notes ?? '',
      });
    }
  }, [profile, form]);

  return (
    <form onSubmit={form.handleSubmit(onSubmit)} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-semibold text-slate-700">Profile Name *</label>
          <input
            {...form.register('name')}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          {form.formState.errors.name ? (
            <p className="mt-1 text-xs text-red-600">{form.formState.errors.name.message}</p>
          ) : null}
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Router ID *</label>
          <input
            type="number"
            {...form.register('router_id', { valueAsNumber: true })}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          {form.formState.errors.router_id ? (
            <p className="mt-1 text-xs text-red-600">{form.formState.errors.router_id.message}</p>
          ) : null}
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Router Profile Name *</label>
          <input
            {...form.register('router_profile_name')}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          {form.formState.errors.router_profile_name ? (
            <p className="mt-1 text-xs text-red-600">{form.formState.errors.router_profile_name.message}</p>
          ) : null}
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Shared Users</label>
          <input
            type="number"
            min={1}
            {...form.register('shared_users', { valueAsNumber: true })}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Rate Limit Up (e.g. 5M)</label>
          <input
            {...form.register('rate_limit_up')}
            placeholder="5M"
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Rate Limit Down (e.g. 10M)</label>
          <input
            {...form.register('rate_limit_down')}
            placeholder="10M"
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Session Timeout (seconds)</label>
          <input
            type="number"
            {...form.register('session_timeout', { valueAsNumber: true })}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Idle Timeout (seconds)</label>
          <input
            type="number"
            {...form.register('idle_timeout', { valueAsNumber: true })}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">MAC Cookie Timeout (e.g. 3d)</label>
          <input
            {...form.register('mac_cookie_timeout')}
            placeholder="3d"
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Login Methods</label>
          <input
            {...form.register('login_methods')}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-slate-700">Status</label>
          <select
            {...form.register('status')}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div>
        <label className="block text-sm font-semibold text-slate-700">Notes</label>
        <textarea
          {...form.register('notes')}
          rows={3}
          className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>

      <div className="flex justify-end gap-2 pt-4 border-t border-slate-200">
        {onCancel ? (
          <Button variant="secondary" type="button" onClick={onCancel}>
            Cancel
          </Button>
        ) : null}
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving...' : profile ? 'Update Profile' : 'Create Profile'}
        </Button>
      </div>
    </form>
  );
};
