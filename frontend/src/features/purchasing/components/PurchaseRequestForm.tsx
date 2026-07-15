import { useState } from 'react';
import type { PurchaseRequestFormValues, Priority } from '../types';

interface Props {
  initialData?: PurchaseRequestFormValues;
  onSubmit: (data: PurchaseRequestFormValues) => void;
  isLoading?: boolean;
}

export const PurchaseRequestForm = ({ initialData, onSubmit, isLoading }: Props) => {
  const [form, setForm] = useState<PurchaseRequestFormValues>(initialData ?? {
    department: '',
    priority: 'normal' as Priority,
    required_date: '',
    notes: '',
    items: [{ product_id: 0, description: '', quantity: 1, estimated_unit_cost: 0, notes: '' }],
  });

  const addItem = () => setForm((f) => ({ ...f, items: [...f.items, { product_id: 0, description: '', quantity: 1, estimated_unit_cost: 0, notes: '' }] }));
  const removeItem = (i: number) => setForm((f) => ({ ...f, items: f.items.filter((_, idx) => idx !== i) }));
  const updateItem = (i: number, field: string, value: string | number) => {
    setForm((f) => ({ ...f, items: f.items.map((item, idx) => idx === i ? { ...item, [field]: value } : item) }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2">
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Department</label>
          <input value={form.department ?? ''} onChange={(e) => setForm({ ...form, department: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. IT, Operations" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Priority</label>
          <select value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value as Priority })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Required Date</label>
          <input type="date" value={form.required_date ?? ''} onChange={(e) => setForm({ ...form, required_date: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <label className="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
        <textarea value={form.notes ?? ''} onChange={(e) => setForm({ ...form, notes: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" rows={3} />
      </div>

      <div>
        <div className="mb-3 flex items-center justify-between">
          <h3 className="font-semibold text-slate-900">Line Items</h3>
          <button type="button" onClick={addItem} className="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Add Item</button>
        </div>
        <div className="space-y-3">
          {form.items.map((item, i) => (
            <div key={i} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div className="grid gap-3 md:grid-cols-4">
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Product ID</label>
                  <input type="number" min={1} value={item.product_id || ''} onChange={(e) => updateItem(i, 'product_id', parseInt(e.target.value) || 0)} className="w-full rounded border px-2 py-1.5 text-sm" required />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Quantity</label>
                  <input type="number" min={0.01} step="0.01" value={item.quantity || ''} onChange={(e) => updateItem(i, 'quantity', parseFloat(e.target.value) || 0)} className="w-full rounded border px-2 py-1.5 text-sm" required />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-500">Est. Unit Cost</label>
                  <input type="number" min={0} step="0.01" value={item.estimated_unit_cost || ''} onChange={(e) => updateItem(i, 'estimated_unit_cost', parseFloat(e.target.value) || 0)} className="w-full rounded border px-2 py-1.5 text-sm" />
                </div>
                <div className="flex items-end">
                  {form.items.length > 1 ? <button type="button" onClick={() => removeItem(i)} className="rounded border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button> : null}
                </div>
              </div>
              <div className="mt-2">
                <input placeholder="Description (optional)" value={item.description ?? ''} onChange={(e) => updateItem(i, 'description', e.target.value)} className="w-full rounded border px-2 py-1.5 text-sm" />
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="flex justify-end gap-3">
        <button type="submit" disabled={isLoading} className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 disabled:opacity-50">
          {isLoading ? 'Saving…' : 'Save Request'}
        </button>
      </div>
    </form>
  );
};
