import type { Warehouse } from '../types';
import { InventoryStatusBadge } from './InventoryStatusBadge';

export const WarehouseTable = ({ warehouses, isLoading, onEdit, onDelete, onView }: { warehouses: Warehouse[]; isLoading: boolean; onEdit?: (warehouse: Warehouse) => void; onDelete?: (warehouse: Warehouse) => void; onView?: (warehouse: Warehouse) => void }) => (
  <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table className="min-w-full"><thead className="bg-slate-50"><tr>{['Warehouse', 'Type', 'Locations', 'Quantity stock', 'Serialized assets', 'Stock value', 'Status', 'Actions'].map((label) => <th key={label} className="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">{label}</th>)}</tr></thead>
      <tbody className="divide-y divide-slate-100">{isLoading ? Array.from({ length: 4 }).map((_, index) => <tr key={index}><td colSpan={8} className="p-3"><div className="h-8 animate-pulse rounded bg-slate-100" /></td></tr>) : warehouses.map((warehouse) => <tr key={warehouse.id} className="hover:bg-slate-50">
        <td className="px-4 py-3"><button type="button" className="text-left" onClick={() => onView?.(warehouse)}><p className="font-semibold text-slate-900">{warehouse.name}</p><p className="font-mono text-xs text-indigo-600">{warehouse.code}</p></button></td>
        <td className="px-4 py-3 text-sm capitalize text-slate-600">{warehouse.type.replaceAll('_', ' ')}</td>
        <td className="px-4 py-3 text-sm tabular-nums">{warehouse.location_count}</td>
        <td className="px-4 py-3 text-sm tabular-nums">{Number(warehouse.quantity_stock).toLocaleString()}</td>
        <td className="px-4 py-3 text-sm tabular-nums">{warehouse.serialized_assets}</td>
        <td className="px-4 py-3 text-sm tabular-nums">PKR {Number(warehouse.stock_value).toLocaleString()}</td>
        <td className="px-4 py-3"><InventoryStatusBadge status={warehouse.status} /></td>
        <td className="px-4 py-3"><div className="flex gap-2">{onEdit && <button type="button" onClick={() => onEdit(warehouse)} className="text-xs font-semibold text-indigo-600">Edit</button>}{onDelete && <button type="button" onClick={() => onDelete(warehouse)} className="text-xs font-semibold text-red-600">Delete</button>}</div></td>
      </tr>)}</tbody>
    </table>
    {!isLoading && warehouses.length === 0 && <p className="py-16 text-center text-sm text-slate-500">No warehouses match the current filters.</p>}
  </div>
);
