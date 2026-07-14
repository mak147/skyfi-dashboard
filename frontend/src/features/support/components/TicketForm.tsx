import { zodResolver } from '@hookform/resolvers/zod';
import { useQuery } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import type { ReactNode } from 'react';
import type { z } from 'zod';

import { Button } from '@/components/ui/button';

import { getCategories, lookup } from '../api/supportApi';
import { ticketSchema } from '../schemas';
import type { TicketFormValues } from '../types';

type FormInput = z.input<typeof ticketSchema>;
type Option = Record<string, unknown>;

const Field = ({ label, error, children }: { label: string; error?: string; children: ReactNode }) => (
  <label className="block text-sm font-medium text-slate-700">
    <span>{label}</span>
    {children}
    {error ? <span className="mt-1 block text-xs text-red-600">{error}</span> : null}
  </label>
);

const Options = ({ items, label }: { items?: Option[]; label: (item: Option) => string }) => (
  <>{items?.map((item) => <option key={String(item.id)} value={Number(item.id)}>{label(item)}</option>)}</>
);

export const TicketForm = ({ initial, onSubmit, isLoading }: {
  initial?: Partial<TicketFormValues>;
  onSubmit: (value: TicketFormValues) => void;
  isLoading?: boolean;
}) => {
  const categories = useQuery({ queryKey: ['support', 'categories'], queryFn: getCategories });
  const customers = useQuery({ queryKey: ['support', 'customer-options'], queryFn: () => lookup('customers') });
  const packages = useQuery({ queryKey: ['support', 'package-options'], queryFn: () => lookup('packages') });
  const routers = useQuery({ queryKey: ['support', 'router-options'], queryFn: () => lookup('routers') });
  const devices = useQuery({ queryKey: ['support', 'device-options'], queryFn: () => lookup('devices') });
  const alerts = useQuery({ queryKey: ['support', 'alert-options'], queryFn: () => lookup('alerts') });
  const { register, handleSubmit, watch, formState: { errors } } = useForm<FormInput>({
    resolver: zodResolver(ticketSchema),
    defaultValues: {
      customer_id: initial?.customer_id,
      connection_id: initial?.connection_id ?? undefined,
      package_id: initial?.package_id ?? undefined,
      pppoe_account_id: initial?.pppoe_account_id ?? undefined,
      hotspot_user_id: initial?.hotspot_user_id ?? undefined,
      router_id: initial?.router_id ?? undefined,
      network_device_id: initial?.network_device_id ?? undefined,
      monitoring_alert_id: initial?.monitoring_alert_id ?? undefined,
      category_id: initial?.category_id,
      priority: initial?.priority ?? 'normal',
      source: initial?.source ?? 'staff',
      subject: initial?.subject ?? '',
      description: initial?.description ?? '',
      resolution: initial?.resolution ?? '',
      root_cause: initial?.root_cause ?? '',
    },
  });
  const customerId = Number(watch('customer_id')) || undefined;
  const connections = useQuery({ queryKey: ['support', 'connection-options', customerId], queryFn: () => lookup('connections', '', customerId) });
  const pppoe = useQuery({ queryKey: ['support', 'pppoe-options', customerId], queryFn: () => lookup('pppoe', '', customerId) });
  const hotspot = useQuery({ queryKey: ['support', 'hotspot-options', customerId], queryFn: () => lookup('hotspot', '', customerId) });
  const input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500';

  return (
    <form className="space-y-6" onSubmit={handleSubmit((value) => onSubmit(ticketSchema.parse(value) as TicketFormValues))}>
      <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 className="font-semibold text-slate-900">Customer and service context</h2>
        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Field label="Customer" error={errors.customer_id?.message as string}>
            <select className={input} {...register('customer_id')}><option value="">Select customer</option><Options items={customers.data} label={(x) => `${String(x.full_name)} · ${String(x.customer_code)}`} /></select>
          </Field>
          <Field label="Connection"><select className={input} {...register('connection_id')}><option value="">No connection</option><Options items={connections.data} label={(x) => `${String(x.connection_number)} · ${String(x.name)}`} /></select></Field>
          <Field label="Package"><select className={input} {...register('package_id')}><option value="">No package</option><Options items={packages.data} label={(x) => String(x.name)} /></select></Field>
          <Field label="PPPoE account"><select className={input} {...register('pppoe_account_id')}><option value="">No PPPoE account</option><Options items={pppoe.data} label={(x) => `${String(x.username)} · ${String(x.status)}`} /></select></Field>
          <Field label="Hotspot user"><select className={input} {...register('hotspot_user_id')}><option value="">No Hotspot user</option><Options items={hotspot.data} label={(x) => `${String(x.username)} · ${String(x.status)}`} /></select></Field>
          <Field label="MikroTik router"><select className={input} {...register('router_id')}><option value="">No router</option><Options items={routers.data} label={(x) => `${String(x.name)} · ${String(x.status)}`} /></select></Field>
          <Field label="Infrastructure device"><select className={input} {...register('network_device_id')}><option value="">No device</option><Options items={devices.data} label={(x) => `${String(x.name)} · ${String(x.device_type)}`} /></select></Field>
          <Field label="Monitoring alert"><select className={input} {...register('monitoring_alert_id')}><option value="">No alert</option><Options items={alerts.data} label={(x) => `${String(x.title)} · ${String(x.severity)}`} /></select></Field>
        </div>
      </section>

      <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 className="font-semibold text-slate-900">Ticket information</h2>
        <div className="mt-4 grid gap-4 sm:grid-cols-3">
          <Field label="Category" error={errors.category_id?.message as string}><select className={input} {...register('category_id')}><option value="">Select category</option>{categories.data?.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></Field>
          <Field label="Priority"><select className={input} {...register('priority')}><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></Field>
          <Field label="Source"><select className={input} {...register('source')}><option value="staff">Staff</option><option value="phone">Phone</option><option value="email">Email</option><option value="portal">Portal</option><option value="monitoring">Monitoring</option><option value="system">System</option></select></Field>
        </div>
        <div className="mt-4 space-y-4">
          <Field label="Subject" error={errors.subject?.message}><input className={input} {...register('subject')} /></Field>
          <Field label="Description" error={errors.description?.message}><textarea rows={7} className={input} {...register('description')} /></Field>
          {initial ? <><Field label="Resolution"><textarea rows={3} className={input} {...register('resolution')} /></Field><Field label="Root cause"><textarea rows={3} className={input} {...register('root_cause')} /></Field></> : null}
        </div>
      </section>
      <div className="flex justify-end"><Button type="submit" isLoading={isLoading}>Save Ticket</Button></div>
    </form>
  );
};
