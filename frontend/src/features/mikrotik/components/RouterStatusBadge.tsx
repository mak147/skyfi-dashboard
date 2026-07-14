import { clsx } from 'clsx';

import type { RouterStatus } from '../types';

const labels: Record<RouterStatus, string> = {
  online: 'Online',
  offline: 'Offline',
  unknown: 'Not checked',
  disabled: 'Disabled',
};

const styles: Record<RouterStatus, string> = {
  online: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
  offline: 'bg-red-50 text-red-700 ring-red-100',
  unknown: 'bg-amber-50 text-amber-700 ring-amber-100',
  disabled: 'bg-slate-100 text-slate-600 ring-slate-200',
};

export const RouterStatusBadge = ({ status }: { status: RouterStatus }) => (
  <span className={clsx('inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', styles[status])}>
    <span className="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
    {labels[status]}
  </span>
);
