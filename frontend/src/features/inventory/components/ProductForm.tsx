import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { productSchema, type ProductSchemaValues } from '../schemas';
import type { CatalogItem, Product } from '../types';

const fieldClass = 'mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500';
const Error = ({ message }: { message?: string }) => message ? <p className="mt-1 text-xs text-red-600">{message}</p> : null;

export const ProductForm = ({ product, categories, models, units, onSubmit, onCancel, isLoading }: { product?: Product | null; categories: CatalogItem[]; models: CatalogItem[]; units: CatalogItem[]; onSubmit: (data: ProductSchemaValues) => void; onCancel: () => void; isLoading?: boolean }) => {
  const form = useForm<ProductSchemaValues>({ resolver: zodResolver(productSchema), mode: 'onTouched', defaultValues: product ? {
    category_id: product.category_id, model_id: product.model_id ?? undefined, unit_id: product.unit_id, sku: product.sku, name: product.name,
    description: product.description ?? '', barcode: product.barcode ?? '', qr_code_value: product.qr_code_value ?? '', tracking_mode: product.tracking_mode,
    standard_cost: Number(product.standard_cost), minimum_stock: Number(product.minimum_stock), reorder_level: Number(product.reorder_level), status: product.status,
  } : { category_id: 0, unit_id: 0, sku: '', name: '', description: '', barcode: '', tracking_mode: 'quantity', standard_cost: 0, minimum_stock: 0, reorder_level: 0, status: 'active' } });
  const input = (name: keyof ProductSchemaValues, label: string, type = 'text') => <label className="text-xs font-semibold text-slate-600">{label}<input type={type} step={type === 'number' ? '0.0001' : undefined} className={fieldClass} {...form.register(name, type === 'number' ? { valueAsNumber: true } : undefined)} /><Error message={form.formState.errors[name]?.message as string | undefined} /></label>;
  return <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5">
    <div className="grid gap-4 sm:grid-cols-2">{input('sku', 'SKU')}{input('name', 'Product name')}
      <label className="text-xs font-semibold text-slate-600">Category<select className={fieldClass} {...form.register('category_id', { valueAsNumber: true })}><option value={0}>Select category</option>{categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select><Error message={form.formState.errors.category_id?.message} /></label>
      <label className="text-xs font-semibold text-slate-600">Model<select className={fieldClass} {...form.register('model_id', { setValueAs: (value) => value === '' ? undefined : Number(value) })}><option value="">No model</option>{models.map((item) => <option key={item.id} value={item.id}>{item.brand_name ? `${item.brand_name} · ` : ''}{item.name}</option>)}</select></label>
      <label className="text-xs font-semibold text-slate-600">Unit<select className={fieldClass} {...form.register('unit_id', { valueAsNumber: true })}><option value={0}>Select unit</option>{units.map((item) => <option key={item.id} value={item.id}>{item.name} ({item.symbol})</option>)}</select><Error message={form.formState.errors.unit_id?.message} /></label>
      <label className="text-xs font-semibold text-slate-600">Tracking<select className={fieldClass} {...form.register('tracking_mode')} disabled={Boolean(product)}><option value="quantity">Quantity</option><option value="serialized">Serialized asset</option></select></label>
      {input('standard_cost', 'Standard cost (PKR)', 'number')}{input('minimum_stock', 'Minimum stock', 'number')}{input('reorder_level', 'Reorder level', 'number')}{input('barcode', 'Barcode')}
      <label className="text-xs font-semibold text-slate-600">Status<select className={fieldClass} {...form.register('status')}><option value="active">Active</option><option value="inactive">Inactive</option><option value="discontinued">Discontinued</option></select></label>
    </div>
    <label className="block text-xs font-semibold text-slate-600">Description<textarea rows={3} className={`${fieldClass} h-auto py-2`} {...form.register('description')} /></label>
    <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" isLoading={isLoading}>{product ? 'Save product' : 'Create product'}</Button></div>
  </form>;
};
