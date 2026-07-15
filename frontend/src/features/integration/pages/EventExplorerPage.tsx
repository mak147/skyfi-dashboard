import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useEvents } from '../api/useIntegration';
import { EventTimeline } from '../components/EventTimeline';
import { IntegrationSkeleton } from '../components/IntegrationSkeleton';

export const EventExplorerPage = () => {
  const [page, setPage] = useState(1);
  const [selectedModule, setSelectedModule] = useState<string | null>(null);
  const { data, isLoading, error } = useEvents(page, selectedModule ?? undefined);

  if (isLoading && !data) {
    return <IntegrationSkeleton />;
  }

  if (error) {
    return <Alert title="Events unavailable">{apiErrorMessage(error)}</Alert>;
  }

  const items = data?.data.map((row) => row.attributes) ?? [];
  const meta = data?.meta;
  const modules = [...new Set(items.map((i) => i.source_module))].sort();

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Integrations</p>
        <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Event Explorer</h1>
        <p className="mt-1 text-sm text-slate-500">Browse and discover all registered system events available for webhook subscriptions.</p>
      </header>

      <EventTimeline
        items={items}
        meta={meta}
        onPageChange={setPage}
        selectedModule={selectedModule}
        modules={modules}
        onModuleChange={(mod) => { setSelectedModule(mod); setPage(1); }}
      />
    </div>
  );
};
