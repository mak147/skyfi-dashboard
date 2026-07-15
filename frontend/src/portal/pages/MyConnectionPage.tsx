import { useQuery } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { getConnection } from '../api/portalApi';
import { CardSkeleton } from '../components/PortalSkeleton';
import { ConnectionCard } from '../components/ConnectionCard';

export const MyConnectionPage = () => {
  const connectionQuery = useQuery({
    queryKey: ['portal', 'connection'],
    queryFn: getConnection,
    staleTime: 2 * 60 * 1000,
  });

  if (connectionQuery.isLoading) {
    return <CardSkeleton rows={6} />;
  }

  if (connectionQuery.error) {
    return (
      <Alert title="Unable to load connection">
        {apiErrorMessage(connectionQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  if (!connectionQuery.data) {
    return <Alert title="No connection found">Your account does not have an active connection.</Alert>;
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">My Connection</h1>
        <p className="mt-1 text-sm text-slate-500">View your service details and package information.</p>
      </div>
      <ConnectionCard connection={connectionQuery.data} />
    </div>
  );
};
