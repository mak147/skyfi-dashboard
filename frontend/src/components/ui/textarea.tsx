import type { TextareaHTMLAttributes } from 'react';
import { clsx } from 'clsx';

export const Textarea = ({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) => (
  <textarea
    className={clsx(
      'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
      className,
    )}
    {...props}
  />
);
