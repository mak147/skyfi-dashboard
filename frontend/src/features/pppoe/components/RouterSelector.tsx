import { useQuery } from '@tanstack/react-query';
import { getRouters } from '@/features/mikrotik/api/mikrotikApi';

interface RouterSelectorProps {
  value: number | '';
  onChange: (routerId: number) => void;
  disabled?: boolean;
  className?: string;
}

export const RouterSelector = ({ value, onChange, disabled = false, className = '' }: RouterSelectorProps) => {
  const { data: routersResponse, isLoading } = useQuery({
    queryKey: ['mikrotik', 'routers', 'selector'],
    queryFn: () => getRouters(1, 100, {}, 'name'),
  });

  const routers = routersResponse?.data.map((r) => r.attributes) ?? [];

  return (
    <select
      disabled={disabled || isLoading}
      value={value}
      onChange={(e) => onChange(Number(e.target.value))}
      className={`h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-slate-50 ${className}`}
    >
      <option value="">Select MikroTik Router...</option>
      {routers.map((router) => (
        <option key={router.id} value={router.id}>
          {router.name} ({router.host}) {router.is_enabled ? '' : '[Disabled]'}
        </option>
      ))}
    </select>
  );
};
