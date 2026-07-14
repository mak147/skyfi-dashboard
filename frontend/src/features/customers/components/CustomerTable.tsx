import { useNavigate } from 'react-router-dom';
import { clsx } from 'clsx';

import { Button } from '@/components/ui/button';

import type { Customer } from '../types';
import { CustomerStatusBadge } from './CustomerStatusBadge';

interface CustomerTableProps {
  customers: Customer[];
  isLoading?: boolean;
  sort: string;
  onSortChange: (sort: string) => void;
  onDelete?: (customer: Customer) => void;
  canUpdate?: boolean;
  canDelete?: boolean;
}

const SortIcon = ({ active, desc }: { active: boolean; desc: boolean }) => (
  <span className={clsx('ml-1 text-xs', active ? 'text-indigo-600' : 'text-slate-300')}>
    {desc ? '▼' : '▲'}
  </span>
);

export const CustomerTable = ({
  customers,
  isLoading,
  sort,
  onSortChange,
  onDelete,
  canUpdate,
  canDelete,
}: CustomerTableProps) => {
  const navigate = useNavigate();

  const handleSort = (field: string) => {
    const currentField = sort.startsWith('-') ? sort.slice(1) : sort;
    const isDesc = sort.startsWith('-');

    if (currentField === field) {
      onSortChange(isDesc ? field : `-${field}`);
    } else {
      onSortChange(`-${field}`);
    }
  };

  const renderHeader = (label: string, field: string) => {
    const active = sort === field || sort === `-${field}`;
    const desc = sort === `-${field}`;

    return (
      <th
        className="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-700"
        onClick={() => handleSort(field)}
      >
        <span className="flex items-center">
          {label}
          <SortIcon active={active} desc={desc} />
        </span>
      </th>
    );
  };

  if (isLoading) {
    return (
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div className="animate-pulse">
          <div className="h-12 bg-slate-50" />
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-14 border-t border-slate-100 bg-white" />
          ))}
        </div>
      </div>
    );
  }

  if (customers.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 shadow-sm">
        <p className="text-sm text-slate-400">No customers found.</p>
        <p className="mt-1 text-xs text-slate-400">Try adjusting your filters or search query.</p>
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full">
          <thead className="bg-slate-50">
            <tr>
              {renderHeader('Code', 'customer_code')}
              {renderHeader('Name', 'full_name')}
              {renderHeader('Phone', 'phone')}
              {renderHeader('City', 'city')}
              {renderHeader('Area', 'area')}
              {renderHeader('Status', 'status')}
              <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {customers.map((customer) => (
              <tr
                key={customer.id}
                className="group cursor-pointer transition hover:bg-slate-50"
                onClick={() => navigate(`/customers/${customer.id}`)}
              >
                <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">
                  {customer.customer_code}
                </td>
                <td className="px-4 py-3 text-sm text-slate-700">
                  <div className="font-medium">{customer.full_name}</div>
                  {customer.email && <div className="text-xs text-slate-400">{customer.email}</div>}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{customer.phone}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{customer.city}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{customer.area}</td>
                <td className="whitespace-nowrap px-4 py-3">
                  <CustomerStatusBadge status={customer.status} />
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right">
                  <div className="flex items-center justify-end gap-2 opacity-0 transition group-hover:opacity-100">
                    {canUpdate && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          navigate(`/customers/${customer.id}/edit`);
                        }}
                      >
                        Edit
                      </Button>
                    )}
                    {canDelete && (
                      <Button
                        variant="danger"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          onDelete?.(customer);
                        }}
                      >
                        Delete
                      </Button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
