import { clsx } from 'clsx';

const positive = ['active', 'in_stock', 'deployed', 'completed', 'posted'];
const warning = ['pending', 'approved', 'in_transit', 'partially_received', 'reserved', 'returned', 'maintenance', 'under_repair'];
const danger = ['damaged', 'lost', 'scrapped', 'cancelled', 'closed', 'reversed', 'discontinued'];

export const InventoryStatusBadge = ({ status }: { status: string }) => (
  <span className={clsx(
    'inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize',
    positive.includes(status) && 'bg-emerald-100 text-emerald-700',
    warning.includes(status) && 'bg-amber-100 text-amber-700',
    danger.includes(status) && 'bg-red-100 text-red-700',
    !positive.includes(status) && !warning.includes(status) && !danger.includes(status) && 'bg-slate-100 text-slate-700',
  )}>
    {status.replaceAll('_', ' ')}
  </span>
);
