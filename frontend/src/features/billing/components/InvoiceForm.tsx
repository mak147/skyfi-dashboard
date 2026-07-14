import { useState } from 'react';
import { Button } from '@/components/ui/button';
import type { InvoiceFormData, InvoiceItemType } from '../types';

const itemTypes: InvoiceItemType[] = ['recurring', 'one_time', 'installation', 'prorated', 'late_fee', 'discount', 'tax', 'custom'];

export const InvoiceForm = ({
  initial,
  onSubmit,
  isLoading,
}: {
  initial?: Partial<InvoiceFormData>;
  onSubmit: (data: InvoiceFormData) => void;
  isLoading?: boolean;
}) => {
  const [data, setData] = useState<InvoiceFormData>({
    customer_id: initial?.customer_id || '',
    connection_id: initial?.connection_id || '',
    package_id: initial?.package_id || '',
    billing_period_start: initial?.billing_period_start || '',
    billing_period_end: initial?.billing_period_end || '',
    issue_date: initial?.issue_date || new Date().toISOString().split('T')[0],
    due_date: initial?.due_date || '',
    notes: initial?.notes || '',
    previous_balance: initial?.previous_balance || '0',
    items: initial?.items?.length ? initial.items : [
      { item_type: 'recurring', description: '', quantity: '1', unit_price: '0', tax_amount: '0', discount_amount: '0' },
    ],
  });

  const updateItem = (index: number, field: keyof InvoiceFormData['items'][number], value: string) => {
    const items = [...data.items];
    items[index] = { ...items[index], [field]: value };
    setData({ ...data, items });
  };

  const addItem = () => {
    setData({
      ...data,
      items: [...data.items, { item_type: 'custom', description: '', quantity: '1', unit_price: '0', tax_amount: '0', discount_amount: '0' }],
    });
  };

  const removeItem = (index: number) => {
    const items = data.items.filter((_, i) => i !== index);
    setData({ ...data, items });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(data);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Customer ID</label>
          <input
            type="number"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.customer_id}
            onChange={(e) => setData({ ...data, customer_id: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Connection ID</label>
          <input
            type="number"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.connection_id}
            onChange={(e) => setData({ ...data, connection_id: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Package ID</label>
          <input
            type="number"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.package_id}
            onChange={(e) => setData({ ...data, package_id: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Previous Balance</label>
          <input
            type="number"
            step="0.01"
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.previous_balance}
            onChange={(e) => setData({ ...data, previous_balance: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Billing Period Start</label>
          <input
            type="date"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.billing_period_start}
            onChange={(e) => setData({ ...data, billing_period_start: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Billing Period End</label>
          <input
            type="date"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.billing_period_end}
            onChange={(e) => setData({ ...data, billing_period_end: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Issue Date</label>
          <input
            type="date"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.issue_date}
            onChange={(e) => setData({ ...data, issue_date: e.target.value })}
          />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Due Date</label>
          <input
            type="date"
            required
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={data.due_date}
            onChange={(e) => setData({ ...data, due_date: e.target.value })}
          />
        </div>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-slate-700">Notes</label>
        <textarea
          rows={3}
          className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          value={data.notes}
          onChange={(e) => setData({ ...data, notes: e.target.value })}
        />
      </div>

      <div>
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-semibold text-slate-900">Invoice Items</h3>
          <Button type="button" size="sm" variant="secondary" onClick={addItem}>
            Add Item
          </Button>
        </div>
        <div className="mt-3 space-y-3">
          {data.items.map((item, index) => (
            <div key={index} className="rounded-lg border border-slate-200 bg-slate-50 p-3">
              <div className="grid gap-3 sm:grid-cols-6">
                <div className="sm:col-span-1">
                  <label className="text-xs text-slate-500">Type</label>
                  <select
                    className="mt-1 w-full rounded-md border border-slate-300 px-2 py-1 text-sm"
                    value={item.item_type}
                    onChange={(e) => updateItem(index, 'item_type', e.target.value)}
                  >
                    {itemTypes.map((t) => (
                      <option key={t} value={t}>
                        {t}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="sm:col-span-2">
                  <label className="text-xs text-slate-500">Description</label>
                  <input
                    type="text"
                    required
                    className="mt-1 w-full rounded-md border border-slate-300 px-2 py-1 text-sm"
                    value={item.description}
                    onChange={(e) => updateItem(index, 'description', e.target.value)}
                  />
                </div>
                <div>
                  <label className="text-xs text-slate-500">Qty</label>
                  <input
                    type="number"
                    step="0.01"
                    className="mt-1 w-full rounded-md border border-slate-300 px-2 py-1 text-sm"
                    value={item.quantity}
                    onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                  />
                </div>
                <div>
                  <label className="text-xs text-slate-500">Unit Price</label>
                  <input
                    type="number"
                    step="0.01"
                    className="mt-1 w-full rounded-md border border-slate-300 px-2 py-1 text-sm"
                    value={item.unit_price}
                    onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                  />
                </div>
                <div className="flex items-end gap-2">
                  <Button type="button" size="sm" variant="danger" onClick={() => removeItem(index)}>
                    Remove
                  </Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="flex justify-end">
        <Button type="submit" isLoading={isLoading}>
          Save Invoice
        </Button>
      </div>
    </form>
  );
};
