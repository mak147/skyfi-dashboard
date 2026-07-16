import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';

import { getCustomer } from '../api/customerApi';
import { CustomerProfile } from '../components/CustomerProfile';

export const CustomerDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const customerId = Number(id);
  const { can } = usePermissions();

  const customerQuery = useQuery({
    queryKey: ['customer', customerId],
    queryFn: () => getCustomer(customerId),
    enabled: !Number.isNaN(customerId) && customerId > 0,
    staleTime: 30_000,
  });

  const canUpdate = can('customers.update');
  const canManage = can('customers.manage');

  if (customerQuery.isLoading) {
    return (
      <div className="space-y-6">
        <div className="h-48 animate-pulse rounded-3xl bg-slate-100" />
        <div className="h-96 animate-pulse rounded-xl bg-slate-100" />
      </div>
    );
  }

  if (customerQuery.error) {
    return (
      <Alert title="Failed to load customer" variant="danger">
        {apiErrorMessage(customerQuery.error, 'Unable to load customer details. Please try again.')}
      </Alert>
    );
  }

  if (!customerQuery.data) {
    return (
      <Alert title="Customer not found" variant="danger">
        The requested customer could not be found.
      </Alert>
    );
  }

  return (
    <CustomerProfile
      customer={customerQuery.data}
      canUpdate={canUpdate}
      canManage={canManage}
    />
  );
};
