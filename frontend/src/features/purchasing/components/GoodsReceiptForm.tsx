import { useState } from 'react';
import type { PurchaseOrder } from '../types';

interface Props {
  order: PurchaseOrder;
  onSubmit: (data: { purchase_order_id: number; items: Array<{ purchase_order_item_id: number; product_id: number; quantity_accepted: number; quantity_damaged: number; quantity_short: number; condition: string; notes?: string }> }) => void;
  isLoading?: boolean;
}

export const GoodsReceiptForm = ({ order, onSubmit, isLoading }: Props) => {
  const items = order.items ?? [];
  const [lines, setLines] = useState(items.map((item) => {
    const remaining = Number(item.quantity_ordered) - Number(item.quantity_received);
    return {
      purchase_order_item_id: item.id,
      product_id: item.product_id,
      quantity_accepted: Math.max(0, remaining),
      quantity_damaged: 0,
      quantity_short: 0,
      condition: 'available',
      notes: '',
      product_name: item.product_name,
      sku: item.sku,
      quantity_ordered: Number(item.quantity_ordered),
      quantity_received: Number(item.quantity_received),
      unit_name: item.unit_name,
    };
  }));

  const updateLine = (index: number, field: string, value: number | string) => {
    setLines((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: value };
      // Auto-calculate short
      const ordered = next[index].quantity_ordered;
      const received = next[index].quantity_received;
      const accepted = next[index].quantity_accepted;
      const damaged = next[index].quantity_damaged;
      next[index].quantity_short = Math.max(0, ordered - received - accepted - damaged);
      return next;
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit({
      purchase_order_id: order.id,
      items: lines.map((l) => ({
        purchase_order_item_id: l.purchase_order_item_id,
        product_id: l.product_id,
        quantity_accepted: l.quantity_accepted,
        quantity_damaged: l.quantity_damaged,
        quantity_short: l.quantity_short,
        condition: l.condition,
        notes: l.notes || undefined,
      })),
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
            <tr>
              <th className="px-3 py-2">Product</th>
              <th className="px-3 py-2">Ordered</th>
              <th className="px-3 py-2">Received</th>
              <th className="px-3 py-2">Accept</th>
              <th className="px-3 py-2">Damaged</th>
              <th className="px-3 py-2">Short</th>
              <th className="px-3 py-2">Condition</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {lines.map((line, i) => (
              <tr key={line.purchase_order_item_id}>
                <td className="px-3 py-2"><span className="font-semibold">{line.product_name}</span><br /><span className="text-xs text-slate-400">{line.sku}</span></td>
                <td className="px-3 py-2 tabular-nums">{line.quantity_ordered} {line.unit_name}</td>
                <td className="px-3 py-2 tabular-nums">{line.quantity_received}</td>
                <td className="px-3 py-2"><input type="number" min={0} step="0.01" value={line.quantity_accepted} onChange={(e) => updateLine(i, 'quantity_accepted', parseFloat(e.target.value) || 0)} className="w-20 rounded border px-2 py-1 text-sm" /></td>
                <td className="px-3 py-2"><input type="number" min={0} step="0.01" value={line.quantity_damaged} onChange={(e) => updateLine(i, 'quantity_damaged', parseFloat(e.target.value) || 0)} className="w-20 rounded border px-2 py-1 text-sm" /></td>
                <td className="px-3 py-2 tabular-nums">{line.quantity_short}</td>
                <td className="px-3 py-2">
                  <select value={line.condition} onChange={(e) => updateLine(i, 'condition', e.target.value)} className="rounded border px-2 py-1 text-sm">
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                    <option value="quarantine">Quarantine</option>
                    <option value="damaged">Damaged</option>
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="flex justify-end">
        <button type="submit" disabled={isLoading} className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-emerald-700 disabled:opacity-50">
          {isLoading ? 'Recording…' : 'Record Receipt'}
        </button>
      </div>
    </form>
  );
};
