import { clsx } from 'clsx';

import type { CustomerStatus } from '../types';

interface CustomerStatusBadgeProps {
  status: CustomerStatus;
}

const statusConfig: Record<CustomerStatus, { label: string; className: string }> = {
  lead: { label: 'Lead', className: 'bg-slate-100 text-slate-700 ring-slate-200' },
  prospect: { label: 'Prospect', className: 'bg-indigo-50 text-indigo-700 ring-indigo-200' },
  active: { label: 'Active', className: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  suspended: { label: 'Suspended', className: 'bg-amber-50 text-amber-700 ring-amber-200' },
  disconnected: { label: 'Disconnected', className: 'bg-red-50 text-red-700 ring-red-200' },
  archived: { label: 'Archived', className: 'bg-gray-100 text-gray-600 ring-gray-200' },
};

export const CustomerStatusBadge = ({ status }: CustomerStatusBadgeProps) => {
  const config = statusConfig[status] ?? statusConfig.lead;

  return (
    <span
      className={clsx(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1',
        config.className,
      )}
    >
      {config.label}
    </span>
  );
};
