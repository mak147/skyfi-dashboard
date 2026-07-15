import type { InventoryDashboard } from '../types';

export const InventoryStatistics = ({ data }: { data: InventoryDashboard }) => {
  const cards = [
    ['Catalog Products', data.total_products.toLocaleString(), 'Active SKU definitions'],
    ['Serialized Assets', data.total_assets.toLocaleString(), `${data.damaged_assets} damaged`],
    ['Stock Value', `PKR ${Number(data.stock_value).toLocaleString()}`, 'Quantity-tracked inventory'],
    ['Asset Value', `PKR ${Number(data.serialized_asset_value).toLocaleString()}`, 'Serialized acquisition value'],
    ['Low Stock', data.low_stock_products.toLocaleString(), 'At or below reorder level'],
    ['Open Transfers', data.pending_transfers.toLocaleString(), `${data.active_warehouses} active warehouses`],
  ];
  return <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">{cards.map(([label, value, detail]) => (
    <article key={label} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
      <p className="mt-2 text-2xl font-bold tabular-nums text-slate-900">{value}</p>
      <p className="mt-1 text-xs text-slate-500">{detail}</p>
    </article>
  ))}</div>;
};
