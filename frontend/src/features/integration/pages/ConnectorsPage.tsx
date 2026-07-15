import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useConnectors } from '../api/useIntegration';
import { ConnectorCards } from '../components/ConnectorCards';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';

export const ConnectorsPage = () => {
  const { data, isLoading, error } = useConnectors();

  if (isLoading && !data) {
    return <IntegrationSkeleton />;
  }

  if (error) {
    return <Alert title="Connectors unavailable">{apiErrorMessage(error)}</Alert>;
  }

  const items = data?.data.map((row) => row.attributes) ?? [];

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Connectors</h1>
        <p className="mt-1 text-sm text-slate-500">
          Configure and test third-party service connectors. Enable or disable connectors and manage credentials.
        </p>
      </header>

      <ConnectorCards items={items} />
    </div>
  );
};
