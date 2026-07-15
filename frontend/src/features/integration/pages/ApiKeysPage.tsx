import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useApiKeys } from '../api/useIntegration';
import { ApiKeyTable } from '../components/ApiKeyTable';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';

export const ApiKeysPage = () => {
  const [page, setPage] = useState(1);
  const { data, isLoading, error } = useApiKeys({ page, per_page: 20 });

  if (isLoading && !data) {
    return <IntegrationSkeleton />;
  }

  if (error) {
    return <Alert title="API keys unavailable">{apiErrorMessage(error)}</Alert>;
  }

  const items = data?.data.map((row) => row.attributes) ?? [];
  const meta = data?.meta;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">API Keys</h1>
        <p className="mt-1 text-sm text-slate-500">
          Manage API keys for external application access. Keys are shown only once upon creation.
        </p>
      </header>

      <ApiKeyTable items={items} meta={meta} onPageChange={setPage} />
    </div>
  );
};
