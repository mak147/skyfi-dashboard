import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getCustomer, updateCustomer } from '../api/customerApi';
import { CustomerForm } from '../components/CustomerForm';
import type { CustomerFormData } from '../types';

export const EditCustomerPage = () => {
  const { id } = useParams<{ id: string }>();
  const customerId = Number(id);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);

  const customerQuery = useQuery({
    queryKey: ['customer', customerId],
    queryFn: () => getCustomer(customerId),
    enabled: !Number.isNaN(customerId) && customerId > 0,
    staleTime: 30_000,
  });

  const mutation = useMutation({
    mutationFn: (data: CustomerFormData) => updateCustomer(customerId, data),
    onSuccess: (customer) => {
      queryClient.invalidateQueries({ queryKey: ['customer', customerId] });
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      navigate(`/customers/${customer.id}`);
    },
    onError: (err) => {
      setError(apiErrorMessage(err, 'Failed to update customer. Please try again.'));
    },
  });

  if (customerQuery.isLoading) {
    return (
      <div className="mx-auto max-w-4xl space-y-6">
        <div className="h-8 w-48 animate-pulse rounded bg-slate-100" />
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

  const customer = customerQuery.data;

  const defaultValues: Partial<CustomerFormData> = {
    full_name: customer.full_name,
    father_husband_name: customer.father_husband_name ?? '',
    cnic: customer.cnic ?? '',
    phone: customer.phone,
    whatsapp: customer.whatsapp ?? '',
    email: customer.email ?? '',
    address: customer.address,
    city: customer.city,
    area: customer.area,
    notes: customer.notes ?? '',
    registration_date: customer.registration_date ?? '',
    installation_date: customer.installation_date ?? '',
    installation_technician_id: customer.installation_technician_id ? String(customer.installation_technician_id) : '',
    emergency_contact_name: customer.emergency_contact_name ?? '',
    emergency_contact_phone: customer.emergency_contact_phone ?? '',
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate(`/customers/${customerId}`)}>
          ← Back
        </Button>
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Edit Customer</h1>
          <p className="mt-1 text-sm text-slate-500">Update customer information for {customer.full_name}.</p>
        </div>
      </div>

      {error && (
        <Alert title="Error" variant="danger">
          {error}
        </Alert>
      )}

      <CustomerForm
        defaultValues={defaultValues}
        onSubmit={(data) => {
          setError(null);
          mutation.mutate(data);
        }}
        isSubmitting={mutation.isPending}
        submitLabel="Update Customer"
      />
    </div>
  );
};
