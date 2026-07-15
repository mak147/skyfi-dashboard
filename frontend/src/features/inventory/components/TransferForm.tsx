import { zodResolver } from '@hookform/resolvers/zod';
import { useFieldArray, useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { transferSchema, type TransferSchemaValues } from '../schemas';
import type { Asset, Product, Warehouse } from '../types';

const control = 'h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500';
export const TransferForm = ({ warehouses, products, locations, assets, onSubmit, onCancel, isLoading }: { warehouses: Warehouse[]; products: Product[]; locations: Array<Record<string, unknown>>; assets: Asset[]; onSubmit: (data: TransferSchemaValues) => void; onCancel?: () => void; isLoading?: boolean }) => {
  const form = useForm<TransferSchemaValues>({ resolver: zodResolver(transferSchema), mode: 'onTouched', defaultValues: { source_warehouse_id: 0, destination_warehouse_id: 0, expected_at: '', notes: '', lines: [{ product_id: 0, source_location_id: 0, destination_location_id: 0, quantity_requested: 1, asset_ids: [] }] } });
  const fields = useFieldArray({ control: form.control, name: 'lines' });
  const source = form.watch('source_warehouse_id');
  const destination = form.watch('destination_warehouse_id');
  const sourceLocations = locations.filter((location) => Number(location.warehouse_id) === source);
  const destinationLocations = locations.filter((location) => Number(location.warehouse_id) === destination);
  const selectedProducts = form.watch('lines').map((line) => products.find((product) => product.id === Number(line.product_id)));
  return <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5">
    <div className="grid gap-4 sm:grid-cols-2">
      <label className="text-xs font-semibold text-slate-600">Source warehouse<select className={`mt-1 ${control}`} {...form.register('source_warehouse_id', { valueAsNumber: true })}><option value={0}>Select source</option>{warehouses.filter((warehouse) => warehouse.status === 'active').map((warehouse) => <option key={warehouse.id} value={warehouse.id}>{warehouse.code} · {warehouse.name}</option>)}</select></label>
      <label className="text-xs font-semibold text-slate-600">Destination warehouse<select className={`mt-1 ${control}`} {...form.register('destination_warehouse_id', { valueAsNumber: true })}><option value={0}>Select destination</option>{warehouses.filter((warehouse) => warehouse.status === 'active').map((warehouse) => <option key={warehouse.id} value={warehouse.id}>{warehouse.code} · {warehouse.name}</option>)}</select>{form.formState.errors.destination_warehouse_id && <p className="mt-1 text-xs text-red-600">{form.formState.errors.destination_warehouse_id.message}</p>}</label>
      <label className="text-xs font-semibold text-slate-600">Expected arrival<input type="datetime-local" className={`mt-1 ${control}`} {...form.register('expected_at')} /></label>
    </div>
    <fieldset><legend className="text-sm font-semibold text-slate-900">Transfer items</legend><div className="mt-3 space-y-3">{fields.fields.map((field, index) => <div key={field.id} className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-[2fr_1.3fr_1.3fr_0.7fr_auto]">
      <select aria-label={`Product ${index + 1}`} className={control} {...form.register(`lines.${index}.product_id`, { valueAsNumber: true })}><option value={0}>Select product</option>{products.map((product) => <option key={product.id} value={product.id}>{product.sku} · {product.name}</option>)}</select>
      <select aria-label={`Source location ${index + 1}`} className={control} {...form.register(`lines.${index}.source_location_id`, { valueAsNumber: true })}><option value={0}>Source location</option>{sourceLocations.map((location) => <option key={String(location.id)} value={Number(location.id)}>{String(location.label)}</option>)}</select>
      <select aria-label={`Destination location ${index + 1}`} className={control} {...form.register(`lines.${index}.destination_location_id`, { valueAsNumber: true })}><option value={0}>Destination location</option>{destinationLocations.map((location) => <option key={String(location.id)} value={Number(location.id)}>{String(location.label)}</option>)}</select>
      <input aria-label={`Quantity ${index + 1}`} type="number" min="0.0001" step="0.0001" className={control} {...form.register(`lines.${index}.quantity_requested`, { valueAsNumber: true })} />
      <Button type="button" variant="ghost" onClick={() => fields.remove(index)} disabled={fields.fields.length === 1}>Remove</Button>
      {selectedProducts[index]?.tracking_mode === 'serialized' && <label className="md:col-span-5 text-xs font-semibold text-slate-600">Serialized assets<select multiple className={`${control} mt-1 h-28 py-2`} {...form.register(`lines.${index}.asset_ids`)}>{assets.filter((asset) => asset.product_id === selectedProducts[index]?.id && asset.warehouse_id === source && asset.status === 'in_stock').map((asset) => <option key={asset.id} value={asset.id}>{asset.asset_tag} · {asset.serial_number}</option>)}</select><span className="mt-1 block font-normal text-slate-400">Select exactly the number of assets entered as quantity.</span></label>}
    </div>)}</div><Button className="mt-3" type="button" variant="secondary" onClick={() => fields.append({ product_id: 0, source_location_id: 0, destination_location_id: 0, quantity_requested: 1, asset_ids: [] })}>Add line</Button></fieldset>
    <label className="block text-xs font-semibold text-slate-600">Notes<textarea rows={3} className={`mt-1 ${control} h-auto py-2`} {...form.register('notes')} /></label>
    {form.formState.errors.lines && <p className="text-sm text-red-600">Review each transfer line and provide valid products, locations, and quantities.</p>}
    <div className="flex justify-end gap-2">{onCancel && <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>}<Button type="submit" isLoading={isLoading}>Create transfer</Button></div>
  </form>;
};
