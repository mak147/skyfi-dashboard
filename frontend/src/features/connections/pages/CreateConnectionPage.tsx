import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { createConnection } from '../api/connectionApi';
import { ConnectionForm } from '../components/ConnectionForm';

export const CreateConnectionPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: createConnection,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['connections'] });
      navigate(`/connections/${data.id}`);
    },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900">New Service Connection</h1>
        <p className="mt-1 text-sm text-slate-500">Provision a new internet service for a customer.</p>
      </div>

      {mutation.error && (
        <Alert title="Error" variant="danger">
          {apiErrorMessage(mutation.error, 'Failed to create connection.')}
        </Alert>
      )}

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <ConnectionForm onSubmit={(data) => mutation.mutate(data)} isLoading={mutation.isPending} />
      </div>
    </div>
  );
};
