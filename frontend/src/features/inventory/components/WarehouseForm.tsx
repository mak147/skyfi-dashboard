import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { warehouseSchema, type WarehouseSchemaValues } from '../schemas';
import type { Warehouse } from '../types';

const input = 'mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500';
export const WarehouseForm = ({ warehouse, onSubmit, onCancel, isLoading }: { warehouse?: Warehouse | null; onSubmit: (data: WarehouseSchemaValues) => void; onCancel: () => void; isLoading?: boolean }) => {
  const form = useForm<WarehouseSchemaValues>({ resolver: zodResolver(warehouseSchema), mode: 'onTouched', defaultValues: warehouse ? {
    code: warehouse.code, name: warehouse.name, type: warehouse.type, status: warehouse.status, manager_user_id: warehouse.manager_user_id ?? undefined,
    address: warehouse.address ?? '', city: warehouse.city ?? '', region: warehouse.region ?? '', notes: warehouse.notes ?? '',
  } : { code: '', name: '', type: 'branch', status: 'active' } });
  return <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5"><div className="grid gap-4 sm:grid-cols-2">
    <label className="text-xs font-semibold text-slate-600">Code<input className={input} {...form.register('code')} />{form.formState.errors.code && <p className="mt-1 text-xs text-red-600">{form.formState.errors.code.message}</p>}</label>
    <label className="text-xs font-semibold text-slate-600">Name<input className={input} {...form.register('name')} />{form.formState.errors.name && <p className="mt-1 text-xs text-red-600">{form.formState.errors.name.message}</p>}</label>
    <label className="text-xs font-semibold text-slate-600">Type<select className={input} {...form.register('type')}>{['main', 'branch', 'technician_vehicle', 'repair_depot', 'site_store', 'other'].map((type) => <option key={type} value={type}>{type.replaceAll('_', ' ')}</option>)}</select></label>
    <label className="text-xs font-semibold text-slate-600">Status<select className={input} {...form.register('status')}>{['active', 'maintenance', 'inactive', 'closed'].map((status) => <option key={status} value={status}>{status}</option>)}</select></label>
    <label className="text-xs font-semibold text-slate-600 sm:col-span-2">Address<input className={input} {...form.register('address')} /></label>
    <label className="text-xs font-semibold text-slate-600">City<input className={input} {...form.register('city')} /></label><label className="text-xs font-semibold text-slate-600">Region<input className={input} {...form.register('region')} /></label>
  </div><label className="block text-xs font-semibold text-slate-600">Notes<textarea className={`${input} h-auto py-2`} rows={3} {...form.register('notes')} /></label><div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" isLoading={isLoading}>{warehouse ? 'Save warehouse' : 'Create warehouse'}</Button></div></form>;
};
