import { clsx } from 'clsx';

import type { DeliveryStatus as Status } from '../types';

const styles: Record<Status, string> = {
  pending: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  queued: 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
  sent: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
  failed: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
  skipped: 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
};

export const DeliveryStatusBadge = ({ status }: { status: Status | string }) => (
  <span
    className={clsx(
      'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
      styles[status as Status] ?? styles.pending,
    )}
  >
    {status}
  </span>
);
