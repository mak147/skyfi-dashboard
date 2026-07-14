import { useNavigate } from 'react-router-dom';

import type { MikrotikRouter } from '../types';
import { RouterStatusBadge } from './RouterStatusBadge';

export const RouterCard = ({ router }: { router: MikrotikRouter }) => {
  const navigate = useNavigate();

  return (
    <button type="button" onClick={() => navigate(`/network/routers/${router.id}`)} className="w-full rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-indigo-200 hover:shadow-card">
      <div className="flex items-start justify-between gap-3"><div><p className="font-semibold text-slate-900">{router.name}</p><p className="mt-1 font-mono text-xs text-slate-500">{router.host}:{router.api_port}</p></div><RouterStatusBadge status={router.last_connection_status} /></div>
      <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-sm"><span className="text-slate-500">{router.site ?? router.location ?? 'No site'}</span><span className="text-slate-700">{router.router_group_name ?? 'Ungrouped'}</span></div>
    </button>
  );
};
