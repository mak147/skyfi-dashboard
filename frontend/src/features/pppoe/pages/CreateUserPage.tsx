import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useCreatePppoeAccount } from '../api/usePppoe';
import { PPPoEForm } from '../components/PPPoEForm';
import type { PppoeFormValues } from '../schemas';

export const CreateUserPage = () => {
  const navigate = useNavigate();
  const createMutation = useCreatePppoeAccount();
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = (values: PppoeFormValues) => {
    setErrorMsg(null);
    createMutation.mutate(values, {
      onSuccess: (account) => {
        navigate(`/network/pppoe/accounts/${account.id}`);
      },
      onError: (err) => {
        setErrorMsg(apiErrorMessage(err) ?? 'Failed to create PPPoE account.');
      },
    });
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Create PPPoE Account</h1>
        <p className="mt-1 text-sm text-slate-500">
          Provision a new PPPoE subscriber credential and automatically push the secret to the target MikroTik router.
        </p>
      </div>

      {errorMsg ? (
        <Alert title="Creation Failed" variant="danger">
          {errorMsg}
        </Alert>
      ) : null}

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <PPPoEForm
          isSubmitting={createMutation.isPending}
          onSubmit={(data) => handleSubmit(data as PppoeFormValues)}
          onCancel={() => navigate('/network/pppoe')}
        />
      </div>
    </div>
  );
};
