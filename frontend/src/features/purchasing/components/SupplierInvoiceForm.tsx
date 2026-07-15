import { useState } from 'react';
import type { SupplierInvoiceFormValues } from '../types';

interface Props {
  onSubmit: (data: SupplierInvoiceFormValues) => void;
  isLoading?: boolean;
}

export const SupplierInvoiceForm = ({ onSubmit, isLoading }: Props) => {
  const [form, setForm] = useState<SupplierInvoiceFormValues>({
    invoice_number: '',
    vendor_id: 0,
    purchase_order_id: null,
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    subtotal: 0,
    tax_amount: 0,
    total_amount: 0,
    currency: 'PKR',
    notes: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div className="grid gap-4 md:grid-cols-2">
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Invoice Number</label>
          <input value={form.invoice_number} onChange={(e) => setForm({ ...form, invoice_number: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Supplier (Vendor ID)</label>
          <input type="number" min={1} value={form.vendor_id || ''} onChange={(e) => setForm({ ...form, vendor_id: parseInt(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Purchase Order ID</label>
          <input type="number" min={1} value={form.purchase_order_id ?? ''} onChange={(e) => setForm({ ...form, purchase_order_id: parseInt(e.target.value) || null })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Currency</label>
          <input value={form.currency} onChange={(e) => setForm({ ...form, currency: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" maxLength={3} />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Invoice Date</label>
          <input type="date" value={form.invoice_date} onChange={(e) => setForm({ ...form, invoice_date: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Due Date</label>
          <input type="date" value={form.due_date ?? ''} onChange={(e) => setForm({ ...form, due_date: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Subtotal</label>
          <input type="number" min={0} step="0.01" value={form.subtotal} onChange={(e) => setForm({ ...form, subtotal: parseFloat(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Tax Amount</label>
          <input type="number" min={0} step="0.01" value={form.tax_amount} onChange={(e) => setForm({ ...form, tax_amount: parseFloat(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold text-slate-700">Total Amount</label>
          <input type="number" min={0} step="0.01" value={form.total_amount} onChange={(e) => setForm({ ...form, total_amount: parseFloat(e.target.value) || 0 })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
      </div>
      <div>
        <label className="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
        <textarea value={form.notes ?? ''} onChange={(e) => setForm({ ...form, notes: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" rows={3} />
      </div>
      <div className="flex justify-end">
        <button type="submit" disabled={isLoading} className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 disabled:opacity-50">
          {isLoading ? 'Saving…' : 'Register Invoice'}
        </button>
      </div>
    </form>
  );
};
