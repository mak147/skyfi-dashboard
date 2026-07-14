import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { getConnection, updateConnection } from '../api/connectionApi';
import { ConnectionForm } from '../components/ConnectionForm';
import type { ConnectionFormData } from '../types';

export const EditConnectionPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: connection, isLoading, error } = useQuery({
    queryKey: ['connections', id],
    queryFn: () => getConnection(Number(id)),
  });

  const mutation = useMutation({
    mutationFn: (data: ConnectionFormData) => updateConnection(Number(id), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['connections', id] });
      navigate(`/connections/${id}`);
    },
  });

  if (isLoading) return <div className="animate-pulse h-64 bg-slate-100 rounded-xl" />;
  if (error || !connection) return <Alert title="Error" variant="danger">{apiErrorMessage(error, 'Connection not found.')}</Alert>;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Edit Connection</h1>
        <p className="mt-1 text-sm text-slate-500">Update service configuration for {connection.connection_number}.</p>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <ConnectionForm 
          initialData={connection} 
          onSubmit={(data) => mutation.mutate(data)} 
          isLoading={mutation.isPending} 
        />
      </div>
    </div>
  );
};
