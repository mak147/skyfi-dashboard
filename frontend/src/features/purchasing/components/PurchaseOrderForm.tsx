import { useState } from 'react';
import type { PurchaseOrderFormValues } from '../types';

interface Props {
  initialData?: PurchaseOrderFormValues;
  onSubmit: (data: PurchaseOrderFormValues) => void;
  isLoading?: boolean;
}

export const PurchaseOrderForm = ({ initialData, onSubmit, isLoading }: Props) => {
  const [form, setForm] = useState<PurchaseOrderFormValues>(initialData ?? {
    vendor_id: 0,
    warehouse_id: null,
    currency: 'PKR',
    tax_rate: 0,
    discount_amount: 0,
    order_date: new Date().toISOString().slice(0, 10),
    expected_delivery_date: '',
    notes: '',
    items: [{ product_id: 0, description: '', quantity_ordered: 1, unit_price: 0 }],
  });

  const addItem = () => setForm((f) => ({ ...f, items: [...f.items, { product_id: 0, description: '', quantity_ordered: 1, unit_price: 0 }] }));
  const removeItem = (i: number) => setForm((f) => ({ ...f, items: f.items.filter((_, idx) => idx !== i) }));
  const updateItem = (i: number, field: string, value: string | number) => {
    setForm((f) => ({ ...f, items: f.items.map((item, idx) => idx === i ? { ...item, [field]: value } : item) }));
  };

  const subtotal = form.items.reduce((sum, item) => sum + (item.quantity_ordered * item.unit_price), 0);
  const taxAmount = subtotal * (form.tax_rate / 100);
  const total = subtotal + taxAmount - form.discount_amount;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 md:grid-cols-3">
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Supplier (Vendor ID)</label>
          <input type="number" min={1} value={form.vendor_id || ''} onChange={(e) => setForm({ ...form, vendor_id: parseInt(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Warehouse ID</label>
          <input type="number" min={1} value={form.warehouse_id ?? ''} onChange={(e) => setForm({ ...form, warehouse_id: parseInt(e.target.value) || null })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Currency</label>
          <input value={form.currency} onChange={(e) => setForm({ ...form, currency: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" maxLength={3} />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Tax Rate (%)</label>
          <input type="number" min={0} max={100} step="0.01" value={form.tax_rate} onChange={(e) => setForm({ ...form, tax_rate: parseFloat(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Discount Amount</label>
          <input type="number" min={0} step="0.01" value={form.discount_amount} onChange={(e) => setForm({ ...form, discount_amount: parseFloat(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Expected Delivery</label>
          <input type="date" value={form.expected_delivery_date ?? ''} onChange={(e) => setForm({ ...form, expected_delivery_date: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <label className="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
        <textarea value={form.notes ?? ''} onChange={(e) => setForm({ ...form, notes: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" rows={2} />
      </div>

      <div>
        <div className="mb-3 flex items-center justify-between">
          <h3 className="font-semibold text-slate-900">Line Items</h3>
          <button type="button" onClick={addItem} className="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Add Item</button>
        </div>
        <div className="space-y-3">
          {form.items.map((item, i) => (
            <div key={i} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div className="grid gap-3 md:grid-cols-5">
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Product ID</label>
                  <input type="number" min={1} value={item.product_id || ''} onChange={(e) => updateItem(i, 'product_id', parseInt(e.target.value) || 0)} className="w-full rounded border px-2 py-1.5 text-sm" required />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Quantity</label>
                  <input type="number" min={0.01} step="0.01" value={item.quantity_ordered || ''} onChange={(e) => updateItem(i, 'quantity_ordered', parseFloat(e.target.value) || 0)} className="w-full rounded border px-2 py-1.5 text-sm" required />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Unit Price</label>
                  <input type="number" min={0} step="0.01" value={item.unit_price || ''} onChange={(e) => updateItem(i, 'unit_price', parseFloat(e.target.value) || 0)} className="w-full rounded border px-2 py-1.5 text-sm" required />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Line Total</label>
                  <p className="rounded border border-slate-200 bg-white px-2 py-1.5 text-sm font-semibold tabular-nums">PKR {(item.quantity_ordered * item.unit_price).toLocaleString()}</p>
                </div>
                <div className="flex items-end">
                  {form.items.length > 1 ? <button type="button" onClick={() => removeItem(i)} className="rounded border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button> : null}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div className="grid gap-2 text-sm md:grid-cols-3">
          <div className="flex justify-between"><span className="text-slate-500">Subtotal:</span><span className="font-semibold tabular-nums">PKR {subtotal.toLocaleString()}</span></div>
          <div className="flex justify-between"><span className="text-slate-500">Tax ({form.tax_rate}%):</span><span className="font-semibold tabular-nums">PKR {taxAmount.toLocaleString()}</span></div>
          <div className="flex justify-between"><span className="text-slate-500">Total:</span><span className="text-lg font-bold tabular-nums text-indigo-700">PKR {Math.max(0, total).toLocaleString()}</span></div>
        </div>
      </div>

      <div className="flex justify-end gap-3">
        <button type="submit" disabled={isLoading} className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 disabled:opacity-50">
          {isLoading ? 'Saving…' : 'Save Purchase Order'}
        </button>
      </div>
    </form>
  );
};
