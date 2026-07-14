import type { LabelHTMLAttributes } from 'react';

import { clsx } from 'clsx';

interface LabelProps extends LabelHTMLAttributes<HTMLLabelElement> {
  required?: boolean;
}

export const Label = ({ required = false, className, children, ...props }: LabelProps) => (
  <label className={clsx('mb-2 block text-xs font-semibold text-slate-600', className)} {...props}>
    {children}
    {required ? <span aria-hidden="true" className="ml-1 text-red-500">*</span> : null}
  </label>
);
