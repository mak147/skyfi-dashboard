import { useNavigate } from 'react-router-dom';
import type { Asset } from '../types';
import { InventoryStatusBadge } from './InventoryStatusBadge';

const location = (asset: Asset) => asset.warehouse_name || asset.customer_name || asset.tower_name || asset.pop_site_name || asset.technician_name || 'Unassigned';

export const AssetTable = ({ assets, isLoading, onEdit, onDelete }: { assets: Asset[]; isLoading: boolean; onEdit?: (asset: Asset) => void; onDelete?: (asset: Asset) => void }) => {
  const navigate = useNavigate();
  return <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table className="min-w-full">
      <thead className="bg-slate-50"><tr>{['Asset / Product', 'Serial / MAC', 'Current location', 'Warranty', 'Cost', 'Status', 'Actions'].map((label) => <th key={label} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</th>)}</tr></thead>
      <tbody className="divide-y divide-slate-100">
        {isLoading ? Array.from({ length: 5 }).map((_, index) => <tr key={index}><td colSpan={7} className="p-3"><div className="h-8 animate-pulse rounded bg-slate-100" /></td></tr>) : assets.map((asset) => (
          <tr key={asset.id} className="cursor-pointer hover:bg-slate-50" onClick={() => navigate(`/inventory/assets/${asset.id}`)}>
            <td className="px-4 py-3"><p className="font-semibold text-slate-900">{asset.asset_tag}</p><p className="text-xs text-slate-500">{asset.product_name} · {asset.sku}</p></td>
            <td className="px-4 py-3 font-mono text-xs text-slate-600"><p>{asset.serial_number}</p><p className="text-slate-400">{asset.mac_address || 'No MAC'}</p></td>
            <td className="px-4 py-3 text-sm text-slate-600"><p>{location(asset)}</p><p className="text-xs capitalize text-slate-400">{asset.assignment_type?.replaceAll('_', ' ') || 'No assignment'}</p></td>
            <td className="px-4 py-3 text-sm text-slate-600">{asset.warranty_expires_at || '—'}</td>
            <td className="px-4 py-3 text-sm tabular-nums">PKR {Number(asset.acquisition_cost).toLocaleString()}</td>
            <td className="px-4 py-3"><InventoryStatusBadge status={asset.status} /></td>
            <td className="px-4 py-3" onClick={(event) => event.stopPropagation()}><div className="flex gap-2">{onEdit && <button type="button" onClick={() => onEdit(asset)} className="text-xs font-semibold text-indigo-600">Edit</button>}{onDelete && <button type="button" onClick={() => onDelete(asset)} className="text-xs font-semibold text-red-600">Retire</button>}</div></td>
          </tr>
        ))}
      </tbody>
    </table>
    {!isLoading && assets.length === 0 && <p className="py-16 text-center text-sm text-slate-500">No serialized assets match the current filters.</p>}
  </div>;
};
