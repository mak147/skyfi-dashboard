import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { assetSchema, type AssetSchemaValues } from '../schemas';
import type { Asset, CatalogItem, Product } from '../types';

const control = 'mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500';
export const AssetForm = ({ asset, products, vendors, locations, onSubmit, onCancel, isLoading }: { asset?: Asset | null; products: Product[]; vendors: CatalogItem[]; locations: Array<Record<string, unknown>>; onSubmit: (data: AssetSchemaValues) => void; onCancel: () => void; isLoading?: boolean }) => {
  const form = useForm<AssetSchemaValues>({ resolver: zodResolver(assetSchema), mode: 'onTouched', defaultValues: asset ? {
    product_id: asset.product_id, vendor_id: asset.vendor_id ?? undefined, network_device_id: asset.network_device_id ?? undefined, asset_tag: asset.asset_tag,
    serial_number: asset.serial_number, mac_address: asset.mac_address ?? '', imei: asset.imei ?? '', barcode: asset.barcode ?? '', purchase_date: asset.purchase_date ?? '',
    acquisition_cost: Number(asset.acquisition_cost), warranty_starts_at: asset.warranty_starts_at ?? '', warranty_expires_at: asset.warranty_expires_at ?? '', status: asset.status, notes: asset.notes ?? '', warehouse_location_id: asset.warehouse_location_id ?? undefined,
  } : { product_id: 0, asset_tag: '', serial_number: '', mac_address: '', acquisition_cost: 0, status: 'in_stock' } });
  const field = (name: keyof AssetSchemaValues, label: string, type = 'text') => <label className="text-xs font-semibold text-slate-600">{label}<input type={type} step={type === 'number' ? '0.0001' : undefined} className={control} {...form.register(name, type === 'number' ? { valueAsNumber: true } : undefined)} />{form.formState.errors[name]?.message && <p className="mt-1 text-xs text-red-600">{String(form.formState.errors[name]?.message)}</p>}</label>;
  return <form className="space-y-5" onSubmit={form.handleSubmit(onSubmit)}>
    <div className="grid gap-4 sm:grid-cols-2">
      <label className="text-xs font-semibold text-slate-600">Serialized product<select className={control} {...form.register('product_id', { valueAsNumber: true })}><option value={0}>Select product</option>{products.filter((product) => product.tracking_mode === 'serialized').map((product) => <option key={product.id} value={product.id}>{product.sku} · {product.name}</option>)}</select></label>
      <label className="text-xs font-semibold text-slate-600">Vendor<select className={control} {...form.register('vendor_id', { setValueAs: (value) => value === '' ? undefined : Number(value) })}><option value="">No vendor</option>{vendors.map((vendor) => <option key={vendor.id} value={vendor.id}>{vendor.name}</option>)}</select></label>
      {field('asset_tag', 'Asset tag')}{field('serial_number', 'Serial number')}{field('mac_address', 'MAC address')}{field('imei', 'IMEI placeholder')}{field('barcode', 'Barcode')}{field('purchase_date', 'Purchase date', 'date')}{field('acquisition_cost', 'Acquisition cost (PKR)', 'number')}{field('warranty_starts_at', 'Warranty starts', 'date')}{field('warranty_expires_at', 'Warranty expires', 'date')}
      <label className="text-xs font-semibold text-slate-600">Warehouse location<select className={control} {...form.register('warehouse_location_id', { setValueAs: (value) => value === '' ? undefined : Number(value) })}><option value="">Unassigned</option>{locations.map((location) => <option key={String(location.id)} value={Number(location.id)}>{String(location.label)}</option>)}</select></label>
      <label className="text-xs font-semibold text-slate-600">Status<select className={control} {...form.register('status')}>{['in_stock', 'reserved', 'under_repair', 'returned', 'damaged'].map((status) => <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>)}</select></label>
    </div>
    <label className="block text-xs font-semibold text-slate-600">Notes<textarea rows={3} className={`${control} h-auto py-2`} {...form.register('notes')} /></label>
    <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" isLoading={isLoading}>{asset ? 'Save asset' : 'Register asset'}</Button></div>
  </form>;
};
