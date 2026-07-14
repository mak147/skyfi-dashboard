import type { InputHTMLAttributes, ReactNode } from 'react';

import { clsx } from 'clsx';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  isError?: boolean;
  leftIcon?: ReactNode;
  rightIcon?: ReactNode;
}

export const Input = ({ isError = false, leftIcon, rightIcon, className, ...props }: InputProps) => (
  <div className="relative">
    {leftIcon ? <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">{leftIcon}</span> : null}
    <input
      className={clsx(
        'h-10 w-full rounded-md border bg-white px-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
        isError ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-slate-300',
        leftIcon && 'pl-10',
        rightIcon && 'pr-10',
        className,
      )}
      aria-invalid={isError || undefined}
      {...props}
    />
    {rightIcon ? <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">{rightIcon}</span> : null}
  </div>
);
