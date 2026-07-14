import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { createCustomer } from '../api/customerApi';
import { CustomerForm } from '../components/CustomerForm';

export const CreateCustomerPage = () => {
  const navigate = useNavigate();
  const [error, setError] = useState<string | null>(null);

  const mutation = useMutation({
    mutationFn: createCustomer,
    onSuccess: (customer) => {
      navigate(`/customers/${customer.id}`);
    },
    onError: (err) => {
      setError(apiErrorMessage(err, 'Failed to create customer. Please try again.'));
    },
  });

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/customers')}>
          ← Back
        </Button>
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Create Customer</h1>
          <p className="mt-1 text-sm text-slate-500">Register a new customer into the SkyFi system.</p>
        </div>
      </div>

      {error && (
        <Alert title="Error" variant="danger">
          {error}
        </Alert>
      )}

      <CustomerForm
        onSubmit={(data) => {
          setError(null);
          mutation.mutate(data);
        }}
        isSubmitting={mutation.isPending}
        submitLabel="Create Customer"
      />
    </div>
  );
};
