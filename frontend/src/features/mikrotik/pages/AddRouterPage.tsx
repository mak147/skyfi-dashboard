import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { createRouter } from '../api/mikrotikApi';
import { RouterForm } from '../components/RouterForm';
import type { RouterFormData } from '../types';

export const AddRouterPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const mutation = useMutation({ mutationFn: createRouter, onSuccess: (router) => { queryClient.invalidateQueries({ queryKey: ['mikrotik', 'routers'] }); navigate(`/network/routers/${router.id}`); } });

  return <div className="mx-auto max-w-4xl space-y-6"><div><h1 className="text-2xl font-bold tracking-tight text-slate-900">Add MikroTik Router</h1><p className="mt-1 text-sm text-slate-500">Register a RouterOS device with a dedicated, TLS-enabled API account.</p></div>{mutation.error ? <Alert title="Unable to add router" variant="danger">{apiErrorMessage(mutation.error)}</Alert> : null}<section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><RouterForm isSubmitting={mutation.isPending} onSubmit={(data: RouterFormData) => mutation.mutate(data)} /></section></div>;
};
