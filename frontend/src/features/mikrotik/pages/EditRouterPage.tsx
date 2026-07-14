import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { getRouter, updateRouter } from '../api/mikrotikApi';
import { RouterForm } from '../components/RouterForm';
import type { RouterFormData } from '../types';

export const EditRouterPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const router = useQuery({ queryKey: ['mikrotik', 'router', id], queryFn: () => getRouter(Number(id)) });
  const mutation = useMutation({ mutationFn: (data: RouterFormData) => updateRouter(Number(id), data), onSuccess: (updated) => { queryClient.invalidateQueries({ queryKey: ['mikrotik', 'routers'] }); queryClient.setQueryData(['mikrotik', 'router', id], updated); navigate(`/network/routers/${id}`); } });
  if (router.isLoading) return <div className="h-80 animate-pulse rounded-xl bg-slate-100" />;
  if (router.error || !router.data) return <Alert title="Router unavailable" variant="danger">{apiErrorMessage(router.error, 'The requested router was not found.')}</Alert>;

  return <div className="mx-auto max-w-4xl space-y-6"><div><h1 className="text-2xl font-bold tracking-tight text-slate-900">Edit {router.data.name}</h1><p className="mt-1 text-sm text-slate-500">Update inventory data or replace the encrypted RouterOS API password.</p></div>{mutation.error ? <Alert title="Unable to save router" variant="danger">{apiErrorMessage(mutation.error)}</Alert> : null}<section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><RouterForm initialRouter={router.data} isSubmitting={mutation.isPending} onSubmit={mutation.mutate} /></section></div>;
};
