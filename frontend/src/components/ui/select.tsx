import { Children, isValidElement, type ReactElement, type ReactNode } from 'react';
import { clsx } from 'clsx';

interface SelectProps {
  value?: string;
  onValueChange?: (value: string) => void;
  children: ReactNode;
}
interface WrapperProps { children?: ReactNode; className?: string; disabled?: boolean }
interface ItemProps { value: string; children: ReactNode }
interface ValueProps { placeholder?: string }

const collect = (children: ReactNode): { items: ReactElement<ItemProps>[]; placeholder?: string; className?: string; disabled?: boolean } => {
  const result: { items: ReactElement<ItemProps>[]; placeholder?: string; className?: string; disabled?: boolean } = { items: [] };
  Children.forEach(children, (child) => {
    if (!isValidElement(child)) return;
    if (child.type === SelectItem) result.items.push(child as ReactElement<ItemProps>);
    const props = child.props as WrapperProps & ValueProps;
    if (child.type === SelectValue) result.placeholder = props.placeholder;
    if (child.type === SelectTrigger) {
      result.className = props.className;
      result.disabled = props.disabled;
    }
    if (props.children) {
      const nested = collect(props.children);
      result.items.push(...nested.items);
      result.placeholder ??= nested.placeholder;
      result.className ??= nested.className;
      result.disabled ??= nested.disabled;
    }
  });
  return result;
};

export const Select = ({ value = '', onValueChange, children }: SelectProps) => {
  const options = collect(children);
  return (
    <select
      value={value}
      disabled={options.disabled}
      onChange={(event) => onValueChange?.(event.target.value)}
      className={clsx('h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500', options.className)}
    >
      {options.placeholder ? <option value="">{options.placeholder}</option> : null}
      {options.items.map((item) => <option key={item.props.value} value={item.props.value}>{item.props.children}</option>)}
    </select>
  );
};
export const SelectTrigger = ({ children }: WrapperProps) => <>{children}</>;
export const SelectValue = (_props: ValueProps) => null;
export const SelectContent = ({ children }: WrapperProps) => <>{children}</>;
export const SelectItem = ({ value, children }: ItemProps) => <option value={value}>{children}</option>;
