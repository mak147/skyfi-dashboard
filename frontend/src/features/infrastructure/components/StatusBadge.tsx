import { clsx } from 'clsx';

interface StatusBadgeProps {
  status: string;
  type: 'pop-site' | 'tower' | 'sector' | 'device';
  size?: 'sm' | 'md';
}

const statusColors: Record<string, Record<string, string>> = {
  'pop-site': {
    planning: 'bg-slate-100 text-slate-700',
    active: 'bg-emerald-100 text-emerald-700',
    maintenance: 'bg-amber-100 text-amber-700',
    decommissioned: 'bg-red-100 text-red-700',
  },
  tower: {
    planning: 'bg-slate-100 text-slate-700',
    active: 'bg-emerald-100 text-emerald-700',
    maintenance: 'bg-amber-100 text-amber-700',
    decommissioned: 'bg-red-100 text-red-700',
  },
  sector: {
    planning: 'bg-slate-100 text-slate-700',
    active: 'bg-emerald-100 text-emerald-700',
    maintenance: 'bg-amber-100 text-amber-700',
    decommissioned: 'bg-red-100 text-red-700',
  },
  device: {
    inventory: 'bg-slate-100 text-slate-700',
    deployed: 'bg-emerald-100 text-emerald-700',
    maintenance: 'bg-amber-100 text-amber-700',
    offline: 'bg-red-100 text-red-700',
    decommissioned: 'bg-stone-100 text-stone-700',
  },
};

const statusLabels: Record<string, string> = {
  planning: 'Planning',
  active: 'Active',
  maintenance: 'Maintenance',
  decommissioned: 'Decommissioned',
  inventory: 'Inventory',
  deployed: 'Deployed',
  offline: 'Offline',
};

export const StatusBadge = ({ status, type, size = 'md' }: StatusBadgeProps) => {
  const colorClass = statusColors[type]?.[status] || 'bg-slate-100 text-slate-700';
  const label = statusLabels[status] || status;

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-xs',
  };

  return (
    <span className={clsx('inline-flex items-center rounded-full font-medium', colorClass, sizeClasses[size])}>
      {label}
    </span>
  );
};
