import { clsx } from 'clsx';
import type { InvoiceStatus } from '../types';

const styles: Record<InvoiceStatus, string> = {
  draft: 'bg-slate-100 text-slate-600 ring-slate-200',
  pending: 'bg-amber-50 text-amber-700 ring-amber-200',
  issued: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
  partially_paid: 'bg-sky-50 text-sky-700 ring-sky-200',
  paid: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  overdue: 'bg-red-50 text-red-700 ring-red-200',
  cancelled: 'bg-slate-100 text-slate-500 ring-slate-200 line-through',
  void: 'bg-slate-100 text-slate-400 ring-slate-200 line-through',
};

export const InvoiceStatusBadge = ({ status }: { status: InvoiceStatus }) => (
  <span className={clsx('inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1', styles[status])}>
    {status.replace('_', ' ')}
  </span>
);
