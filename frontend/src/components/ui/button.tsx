import type { ButtonHTMLAttributes, ReactElement, ReactNode } from 'react';

import { Children, cloneElement, isValidElement } from 'react';
import { clsx } from 'clsx';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'link';
  size?: 'sm' | 'md' | 'lg' | 'icon';
  isLoading?: boolean;
  asChild?: boolean;
  children: ReactNode;
}

const variantClasses: Record<NonNullable<ButtonProps['variant']>, string> = {
  primary: 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 active:bg-indigo-800',
  secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700',
  danger: 'bg-red-600 text-white shadow-sm hover:bg-red-700 active:bg-red-800',
  ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
  link: 'bg-transparent text-indigo-600 hover:text-indigo-700 hover:underline',
};

const sizeClasses: Record<NonNullable<ButtonProps['size']>, string> = {
  sm: 'min-h-9 px-3 text-xs',
  md: 'min-h-10 px-4 text-sm',
  lg: 'min-h-11 px-5 text-sm',
  icon: 'h-10 w-10 p-0',
};

export const Button = ({
  variant = 'primary',
  size = 'md',
  isLoading = false,
  asChild = false,
  disabled,
  children,
  className,
  ...props
}: ButtonProps) => {
  const classes = clsx(
    'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100',
    variantClasses[variant],
    sizeClasses[size],
    className,
  );

  if (asChild) {
    const child = Children.only(children);
    if (!isValidElement(child)) {
      return null;
    }

    return cloneElement(child as ReactElement<{ className?: string; disabled?: boolean }>, {
      className: clsx(classes, (child.props as { className?: string }).className),
      disabled: disabled || isLoading,
    });
  }

  return (
    <button
      className={classes}
      disabled={disabled || isLoading}
      {...props}
    >
      {isLoading ? (
        <span aria-hidden="true" className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
      ) : null}
      {children}
    </button>
  );
};
