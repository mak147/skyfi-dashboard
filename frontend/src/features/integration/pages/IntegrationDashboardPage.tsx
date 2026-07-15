import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useIntegrationDashboard } from '../api/useIntegration';
import { IntegrationDashboardStats } from '../components/IntegrationDashboardStats';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';

export const IntegrationDashboardPage = () => {
  const dashboard = useIntegrationDashboard();

  if (dashboard.isLoading && !dashboard.data) {
    return <IntegrationSkeleton />;
  }

  if (dashboard.error) {
    return <Alert title="Integration dashboard unavailable">{apiErrorMessage(dashboard.error)}</Alert>;
  }

  const data = dashboard.data!;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">API Gateway &amp; Webhooks</h1>
        <p className="mt-1 text-sm text-slate-500">
          Centralized platform for API management, webhook delivery, event routing, and third-party connectors.
        </p>
      </header>

      <IntegrationDashboardStats data={data} />
    </div>
  );
};
