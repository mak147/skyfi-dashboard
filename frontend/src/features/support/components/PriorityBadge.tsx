import { clsx } from 'clsx';import type { TicketPriority } from '../types';
const tones:Record<TicketPriority,string>={low:'bg-slate-100 text-slate-700',normal:'bg-blue-100 text-blue-700',high:'bg-amber-100 text-amber-800',urgent:'bg-red-100 text-red-700'};
export const PriorityBadge=({priority}:{priority:TicketPriority})=><span className={clsx('inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize',tones[priority])}>{priority}</span>;
