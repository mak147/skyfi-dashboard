import { useNavigate } from 'react-router-dom';

import { Button } from '@/components/ui/button';

import type { Customer } from '../types';
import { CustomerStatusBadge } from './CustomerStatusBadge';

interface CustomerCardProps {
  customer: Customer;
  onDelete?: (customer: Customer) => void;
  canUpdate?: boolean;
  canDelete?: boolean;
}

export const CustomerCard = ({ customer, onDelete, canUpdate, canDelete }: CustomerCardProps) => {
  const navigate = useNavigate();

  return (
    <div
      className="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
      onClick={() => navigate(`/customers/${customer.id}`)}
    >
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs font-semibold text-slate-400">{customer.customer_code}</p>
          <h3 className="mt-1 text-sm font-semibold text-slate-900">{customer.full_name}</h3>
        </div>
        <CustomerStatusBadge status={customer.status} />
      </div>

      <div className="mt-3 space-y-1 text-sm text-slate-600">
        <p>
          <span className="text-slate-400">Phone:</span> {customer.phone}
        </p>
        {customer.email && (
          <p>
            <span className="text-slate-400">Email:</span> {customer.email}
          </p>
        )}
        <p>
          <span className="text-slate-400">Location:</span> {customer.area}, {customer.city}
        </p>
      </div>

      <div className="mt-4 flex gap-2">
        {canUpdate && (
          <Button
            variant="secondary"
            size="sm"
            className="flex-1"
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
            className="flex-1"
            onClick={(e) => {
              e.stopPropagation();
              onDelete?.(customer);
            }}
          >
            Delete
          </Button>
        )}
      </div>
    </div>
  );
};
