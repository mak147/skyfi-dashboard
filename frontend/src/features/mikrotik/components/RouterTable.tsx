import { useNavigate } from 'react-router-dom';

import { Button } from '@/components/ui/button';

import type { MikrotikRouter } from '../types';
import { RouterStatusBadge } from './RouterStatusBadge';

interface RouterTableProps {
  routers: MikrotikRouter[];
  isLoading: boolean;
  canUpdate: boolean;
  onToggleEnabled: (router: MikrotikRouter) => void;
}

export const RouterTable = ({ routers, isLoading, canUpdate, onToggleEnabled }: RouterTableProps) => {
  const navigate = useNavigate();

  return (
    <div className="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3">Router</th><th className="px-4 py-3">Site</th><th className="px-4 py-3">Group / tags</th><th className="px-4 py-3">Version</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {isLoading ? Array.from({ length: 5 }).map((_, index) => <tr key={index}><td colSpan={6} className="px-4 py-5"><div className="h-5 animate-pulse rounded bg-slate-100" /></td></tr>) : null}
            {!isLoading && routers.length === 0 ? <tr><td colSpan={6} className="px-4 py-12 text-center text-sm text-slate-500">No routers match the current filters.</td></tr> : null}
            {!isLoading && routers.map((router) => (
              <tr key={router.id} className="cursor-pointer hover:bg-slate-50" onClick={() => navigate(`/network/routers/${router.id}`)}>
                <td className="px-4 py-3"><p className="font-semibold text-slate-900">{router.name}</p><p className="mt-0.5 font-mono text-xs text-slate-500">{router.host}:{router.api_port}</p></td>
                <td className="px-4 py-3 text-sm text-slate-600">{router.site ?? router.location ?? '—'}</td>
                <td className="px-4 py-3"><p className="text-sm text-slate-700">{router.router_group_name ?? 'Ungrouped'}</p><div className="mt-1 flex flex-wrap gap-1">{router.tags.map((tag) => <span key={tag.id} className="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">{tag.name}</span>)}</div></td>
                <td className="px-4 py-3 text-sm text-slate-600">{router.routeros_version ?? '—'}</td>
                <td className="px-4 py-3"><RouterStatusBadge status={router.last_connection_status} /></td>
                <td className="px-4 py-3 text-right" onClick={(event) => event.stopPropagation()}>{canUpdate ? <Button size="sm" variant="secondary" onClick={() => onToggleEnabled(router)}>{router.is_enabled ? 'Disable' : 'Enable'}</Button> : null}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
