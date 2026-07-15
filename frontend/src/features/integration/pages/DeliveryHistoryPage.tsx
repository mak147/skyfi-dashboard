import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useDeliveries } from '../api/useIntegration';
import { DeliveryHistory } from '../components/DeliveryHistory';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';
import type { IntegrationFilters } from '../types';

export const DeliveryHistoryPage = () => {
  const [filters, setFilters] = useState<IntegrationFilters>({ page: 1, per_page: 20 });
  const { data, isLoading, error } = useDeliveries(filters);

  if (isLoading && !data) {
    return <IntegrationSkeleton />;
  }

  if (error) {
    return <Alert title="Delivery history unavailable">{apiErrorMessage(error)}</Alert>;
  }

  const items = data?.data.map((row) => row.attributes) ?? [];
  const meta = data?.meta;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Delivery History</h1>
        <p className="mt-1 text-sm text-slate-500">Track webhook delivery attempts, statuses, and retry failed deliveries.</p>
      </header>

      <DeliveryHistory items={items} meta={meta} onPageChange={(p) => setFilters((f) => ({ ...f, page: p }))} />
    </div>
  );
};
