import type { AssetTimelineItem } from '../types';

export const AssetTimeline = ({ items, isLoading }: { items: AssetTimelineItem[]; isLoading?: boolean }) => (
  <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 className="text-lg font-semibold text-slate-900">Asset timeline</h2>
    {isLoading ? <div className="mt-4 h-40 animate-pulse rounded bg-slate-100" /> : <ol className="mt-5 space-y-0">
      {items.map((item, index) => <li key={item.id} className="relative flex gap-4 pb-6">
        {index < items.length - 1 && <span className="absolute left-[7px] top-4 h-full w-px bg-slate-200" aria-hidden="true" />}
        <span className="relative mt-1.5 h-3.5 w-3.5 shrink-0 rounded-full border-2 border-indigo-600 bg-white" aria-hidden="true" />
        <div><div className="flex flex-wrap items-center gap-2"><p className="font-semibold capitalize text-slate-900">{item.type.replaceAll('_', ' ')}</p>{item.old_status && item.new_status && <span className="text-xs text-slate-400">{item.old_status.replaceAll('_', ' ')} → {item.new_status.replaceAll('_', ' ')}</span>}</div><p className="mt-1 text-sm text-slate-600">{item.description}</p><p className="mt-1 text-xs text-slate-400">{new Date(item.occurred_at).toLocaleString()} · {item.actor_name || 'System'}</p></div>
      </li>)}
      {items.length === 0 && <li className="py-10 text-center text-sm text-slate-500">No asset events have been recorded.</li>}
    </ol>}
  </div>
);
