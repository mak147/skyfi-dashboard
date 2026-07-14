import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { usePppoeAccount, useUpdatePppoeAccount } from '../api/usePppoe';
import { PPPoEForm } from '../components/PPPoEForm';
import type { EditPppoeFormValues } from '../schemas';

export const EditUserPage = () => {
  const { id } = useParams<{ id: string }>();
  const accountId = Number(id);
  const navigate = useNavigate();

  const { data: account, isLoading: queryLoading, error: queryError } = usePppoeAccount(accountId);
  const updateMutation = useUpdatePppoeAccount();
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = (values: EditPppoeFormValues) => {
    setErrorMsg(null);
    updateMutation.mutate(
      { id: accountId, data: values },
      {
        onSuccess: (updated) => {
          navigate(`/network/pppoe/accounts/${updated.id}`);
        },
        onError: (err) => {
          setErrorMsg(apiErrorMessage(err) ?? 'Failed to update PPPoE account.');
        },
      }
    );
  };

  if (queryLoading) {
    return (
      <div className="mx-auto max-w-4xl space-y-6 animate-pulse">
        <div className="h-8 w-1/3 rounded bg-slate-200" />
        <div className="h-96 rounded-xl bg-slate-100" />
      </div>
    );
  }

  if (queryError || !account) {
    return (
      <Alert title="Error" variant="danger">
        {apiErrorMessage(queryError) ?? 'PPPoE account not found.'}
      </Alert>
    );
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Edit PPPoE Account: {account.username}</h1>
        <p className="mt-1 text-sm text-slate-500">
          Modify secret configuration, profile, rate limits, and push updates directly to the target MikroTik router.
        </p>
      </div>

      {errorMsg ? (
        <Alert title="Update Failed" variant="danger">
          {errorMsg}
        </Alert>
      ) : null}

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <PPPoEForm
          initialAccount={account}
          isSubmitting={updateMutation.isPending}
          onSubmit={(data) => handleSubmit(data as EditPppoeFormValues)}
          onCancel={() => navigate(`/network/pppoe/accounts/${accountId}`)}
        />
      </div>
    </div>
  );
};
