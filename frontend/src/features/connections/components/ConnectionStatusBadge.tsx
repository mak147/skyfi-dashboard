import React from 'react';
import { clsx } from 'clsx';
import type { ConnectionStatus } from '../types';

interface ConnectionStatusBadgeProps {
  status: ConnectionStatus;
  className?: string;
}

const statusStyles: Record<ConnectionStatus, string> = {
  pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  scheduled: 'bg-blue-100 text-blue-800 border-blue-200',
  installing: 'bg-indigo-100 text-indigo-800 border-indigo-200',
  active: 'bg-green-100 text-green-800 border-green-200',
  suspended: 'bg-orange-100 text-orange-800 border-orange-200',
  disconnected: 'bg-red-100 text-red-800 border-red-200',
  cancelled: 'bg-slate-100 text-slate-800 border-slate-200',
  archived: 'bg-gray-100 text-gray-800 border-gray-200',
};

export const ConnectionStatusBadge: React.FC<ConnectionStatusBadgeProps> = ({ status, className }) => {
  return (
    <span
      className={clsx(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize',
        statusStyles[status],
        className
      )}
    >
      {status}
    </span>
  );
};
