import type { Product } from '../types';
import { InventoryStatusBadge } from './InventoryStatusBadge';

export const ProductTable = ({ products, isLoading, onEdit, onDelete }: { products: Product[]; isLoading: boolean; onEdit?: (product: Product) => void; onDelete?: (product: Product) => void }) => (
  <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table className="min-w-full">
      <thead className="bg-slate-50"><tr>{['SKU / Product', 'Category', 'Tracking', 'Available', 'Reorder', 'Cost', 'Status', 'Actions'].map((label) => <th key={label} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</th>)}</tr></thead>
      <tbody className="divide-y divide-slate-100">
        {isLoading ? Array.from({ length: 5 }).map((_, index) => <tr key={index}><td colSpan={8} className="p-3"><div className="h-8 animate-pulse rounded bg-slate-100" /></td></tr>) : products.map((product) => (
          <tr key={product.id} className="hover:bg-slate-50">
            <td className="px-4 py-3"><p className="font-semibold text-slate-900">{product.name}</p><p className="font-mono text-xs text-indigo-600">{product.sku}</p></td>
            <td className="px-4 py-3 text-sm text-slate-600"><p>{product.category_name}</p><p className="text-xs text-slate-400">{[product.brand_name, product.model_name].filter(Boolean).join(' · ') || '—'}</p></td>
            <td className="px-4 py-3 text-sm capitalize text-slate-600">{product.tracking_mode}</td>
            <td className="px-4 py-3 text-sm font-semibold tabular-nums">{Number(product.total_stock).toLocaleString()} {product.unit_symbol}</td>
            <td className="px-4 py-3 text-sm tabular-nums text-slate-600">{Number(product.reorder_level).toLocaleString()}</td>
            <td className="px-4 py-3 text-sm tabular-nums text-slate-600">PKR {Number(product.standard_cost).toLocaleString()}</td>
            <td className="px-4 py-3"><InventoryStatusBadge status={product.status} /></td>
            <td className="px-4 py-3"><div className="flex gap-2">{onEdit && <button type="button" onClick={() => onEdit(product)} className="text-xs font-semibold text-indigo-600 hover:underline">Edit</button>}{onDelete && <button type="button" onClick={() => onDelete(product)} className="text-xs font-semibold text-red-600 hover:underline">Delete</button>}</div></td>
          </tr>
        ))}
      </tbody>
    </table>
    {!isLoading && products.length === 0 && <p className="py-16 text-center text-sm text-slate-500">No products match the current filters.</p>}
  </div>
);
