import { zodResolver } from '@hookform/resolvers/zod';
import { useQuery } from '@tanstack/react-query';
import { Controller, useForm, type Resolver } from 'react-hook-form';

import { FormField } from '@/components/common/form-field';
import { Button } from '@/components/ui/button';

import { getRouterGroups, getRouterTags } from '../api/mikrotikApi';
import { createRouterSchema, editRouterSchema } from '../schemas';
import type { MikrotikRouter, RouterFormData } from '../types';

type FormValues = Omit<RouterFormData, 'api_password'> & { api_password: string };

interface RouterFormProps {
  initialRouter?: MikrotikRouter;
  isSubmitting: boolean;
  onSubmit: (data: RouterFormData) => void;
}

export const RouterForm = ({ initialRouter, isSubmitting, onSubmit }: RouterFormProps) => {
  const groups = useQuery({ queryKey: ['mikrotik', 'router-groups'], queryFn: getRouterGroups });
  const tags = useQuery({ queryKey: ['mikrotik', 'router-tags'], queryFn: getRouterTags });
  const requiresPassword = !initialRouter;
  const schema = requiresPassword ? createRouterSchema : editRouterSchema;
  const form = useForm<FormValues>({
    resolver: zodResolver(schema) as Resolver<FormValues>,
    mode: 'onTouched',
    defaultValues: {
      name: initialRouter?.name ?? '', host: initialRouter?.host ?? '', api_port: initialRouter?.api_port ?? 8729,
      api_username: initialRouter?.api_username ?? '', api_password: '', router_group_id: initialRouter?.router_group_id ?? null,
      tag_ids: initialRouter?.tags.map((tag) => tag.id) ?? [], location: initialRouter?.location ?? '', site: initialRouter?.site ?? '',
      notes: initialRouter?.notes ?? '', is_enabled: initialRouter?.is_enabled ?? true,
    },
  });

  return (
    <form className="space-y-8" onSubmit={form.handleSubmit((values) => onSubmit({ ...values, api_password: values.api_password || undefined }))}>
      <fieldset><legend className="text-base font-semibold text-slate-900">Router identity</legend><p className="mt-1 text-sm text-slate-500">Use a recognisable name and location for the operations team.</p>
        <div className="mt-5 grid gap-5 md:grid-cols-2"><FormField control={form.control} name="name" label="Router name" placeholder="Core Router Lahore" /><FormField control={form.control} name="site" label="Site" placeholder="Lahore POP" /><FormField control={form.control} name="location" label="Location" placeholder="Johar Town, Lahore" />
          <Controller control={form.control} name="router_group_id" render={({ field }) => <label className="text-sm font-medium text-slate-700">Router group<select className="mt-2 h-10 w-full rounded-md border border-slate-300 px-3 text-sm" value={field.value ?? ''} onChange={(event) => field.onChange(event.target.value ? Number(event.target.value) : null)}><option value="">No group</option>{groups.data?.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}</select></label>} />
        </div>
      </fieldset>
      <fieldset className="border-t border-slate-200 pt-7"><legend className="text-base font-semibold text-slate-900">TLS API connection</legend><p className="mt-1 text-sm text-slate-500">SkyFi connects through RouterOS api-ssl. Configure router firewall access and a trusted certificate before testing.</p>
        <div className="mt-5 grid gap-5 md:grid-cols-2"><FormField control={form.control} name="host" label="Host / IP address" placeholder="10.0.0.1" autoComplete="off" /><FormField control={form.control} name="api_username" label="API username" placeholder="skyfi-api" autoComplete="username" />
          <label className="text-sm font-medium text-slate-700">TLS API port<input type="number" className="mt-2 h-10 w-full rounded-md border border-slate-300 px-3 text-sm" {...form.register('api_port', { valueAsNumber: true })} /><span className="mt-1 block text-xs font-normal text-slate-500">Default RouterOS api-ssl port: 8729.</span></label>
          <FormField control={form.control} name="api_password" label={requiresPassword ? 'API password' : 'Replace API password'} type="password" autoComplete="new-password" placeholder={requiresPassword ? '' : 'Leave blank to retain the encrypted password'} />
        </div>
      </fieldset>
      <fieldset className="border-t border-slate-200 pt-7"><legend className="text-base font-semibold text-slate-900">Organisation</legend><div className="mt-5"><p className="text-sm font-medium text-slate-700">Router tags</p><div className="mt-3 flex flex-wrap gap-3">{tags.data?.map((tag) => <Controller key={tag.id} control={form.control} name="tag_ids" render={({ field }) => { const selected = field.value.includes(tag.id); return <label className="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-sm text-slate-700"><input type="checkbox" checked={selected} onChange={() => field.onChange(selected ? field.value.filter((id) => id !== tag.id) : [...field.value, tag.id])} /><span className="h-2 w-2 rounded-full" style={{ backgroundColor: tag.color ?? '#64748b' }} />{tag.name}</label>; }} />)}</div></div>
        <label className="mt-5 block text-sm font-medium text-slate-700">Notes<textarea className="mt-2 min-h-28 w-full rounded-md border border-slate-300 p-3 text-sm" {...form.register('notes')} placeholder="Maintenance window, rack location, or operational notes." /></label>
        <Controller control={form.control} name="is_enabled" render={({ field }) => <label className="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><input className="mt-0.5" type="checkbox" checked={field.value} onChange={(event) => field.onChange(event.target.checked)} /><span><span className="block font-semibold">Enable router integration</span><span className="mt-1 block text-xs text-slate-500">Disabled routers are retained in inventory but cannot be tested, discovered, or health checked.</span></span></label>} />
      </fieldset>
      <div className="flex justify-end border-t border-slate-200 pt-6"><Button type="submit" size="lg" isLoading={isSubmitting}>{initialRouter ? 'Save router changes' : 'Add router'}</Button></div>
    </form>
  );
};
