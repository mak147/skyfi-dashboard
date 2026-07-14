import type { ReactNode } from 'react';

import { clsx } from 'clsx';

interface AlertProps {
  title?: string;
  children: ReactNode;
  variant?: 'danger' | 'info' | 'success';
}

const variants: Record<NonNullable<AlertProps['variant']>, string> = {
  danger: 'border-red-200 bg-red-50 text-red-800',
  info: 'border-indigo-200 bg-indigo-50 text-indigo-800',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
};

export const Alert = ({ title, children, variant = 'danger' }: AlertProps) => (
  <div className={clsx('rounded-md border px-4 py-3 text-sm', variants[variant])} role="alert">
    {title ? <p className="font-semibold">{title}</p> : null}
    <div className={title ? 'mt-1' : undefined}>{children}</div>
  </div>
);
