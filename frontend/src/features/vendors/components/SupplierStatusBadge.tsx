import { clsx } from 'clsx';

const STATUS_MAP: Record<string, { label: string; tone: string }> = {
  // Vendor Status
  active: { label: 'Active', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' },
  inactive: { label: 'Archived / Inactive', tone: 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-400' },
  on_hold: { label: 'On Hold', tone: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300' },

  // Contract Status
  draft: { label: 'Draft', tone: 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300' },
  expiring: { label: 'Expiring Soon', tone: 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-950/40 dark:text-orange-300' },
  expired: { label: 'Expired', tone: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/40 dark:text-red-300' },
  terminated: { label: 'Terminated', tone: 'bg-slate-200 text-slate-800 ring-slate-300 dark:bg-slate-900 dark:text-slate-400' },

  // Quotation Status
  received: { label: 'Received', tone: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300' },
  under_review: { label: 'Under Review', tone: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300' },
  accepted: { label: 'Accepted', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' },
  rejected: { label: 'Rejected', tone: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/40 dark:text-red-300' },
};

export const SupplierStatusBadge = ({ status }: { status: string }) => {
  const config = STATUS_MAP[status] ?? {
    label: status.replaceAll('_', ' '),
    tone: 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-400',
  };
  return (
    <span className={clsx('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize', config.tone)}>
      {config.label}
    </span>
  );
};
