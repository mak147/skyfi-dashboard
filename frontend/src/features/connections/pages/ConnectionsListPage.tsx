import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button } from '@/components/ui/button';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { usePermissions } from '@/hooks/usePermissions';

import { getConnections } from '../api/connectionApi';
import { ConnectionTable } from '../components/ConnectionTable';
import { ConnectionCard } from '../components/ConnectionCard';
import { ConnectionFiltersBar } from '../components/ConnectionFilters';
import type { ConnectionFilters } from '../types';

export const ConnectionsListPage = () => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const { can } = usePermissions();

  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const perPage = 15;
  const sort = searchParams.get('sort') ?? '-created_at';

  const [filters, setFilters] = useState<ConnectionFilters>({
    status: (searchParams.get('status') as any) || undefined,
    type: (searchParams.get('type') as any) || undefined,
    search: searchParams.get('search') || undefined,
  });

  const updateSearchParams = (newFilters: ConnectionFilters, newPage: number, newSort: string) => {
    const params = new URLSearchParams();
    if (newPage > 1) params.set('page', String(newPage));
    if (newSort !== '-created_at') params.set('sort', newSort);
    if (newFilters.status) params.set('status', newFilters.status);
    if (newFilters.type) params.set('type', newFilters.type);
    if (newFilters.search) params.set('search', newFilters.search);
    setSearchParams(params, { replace: true });
  };

  const handleFiltersChange = (newFilters: ConnectionFilters) => {
    setFilters(newFilters);
    updateSearchParams(newFilters, 1, sort);
  };

  const handleSortChange = (newSort: string) => {
    updateSearchParams(filters, page, newSort);
  };

  const connectionsQuery = useQuery({
    queryKey: ['connections', page, perPage, filters, sort],
    queryFn: () => getConnections(page, perPage, filters, sort),
  });

  const connections = connectionsQuery.data?.data.map((d) => d.attributes) ?? [];
  const meta = connectionsQuery.data?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Service Connections</h1>
          <p className="mt-1 text-sm text-slate-500">Manage internet services linked to customers and packages.</p>
        </div>
        {can('connections.create') && (
          <Button onClick={() => navigate('/connections/new')}>
            New Connection
          </Button>
        )}
      </div>

      <ConnectionFiltersBar filters={filters} onChange={handleFiltersChange} />

      {connectionsQuery.error && (
        <Alert title="Error" variant="danger">
          {apiErrorMessage(connectionsQuery.error, 'Failed to load connections.')}
        </Alert>
      )}

      <ConnectionTable
        connections={connections}
        isLoading={connectionsQuery.isLoading}
        sort={sort}
        onSortChange={handleSortChange}
        canUpdate={can('connections.update')}
      />

      {/* Mobile view */}
      <div className="grid gap-4 md:hidden">
        {connectionsQuery.isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-32 animate-pulse rounded-xl bg-slate-100" />
          ))
        ) : (
          connections.map((connection) => (
            <ConnectionCard key={connection.id} connection={connection} />
          ))
        )}
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <p className="text-sm text-slate-500">
            Showing <span className="font-medium">{(meta.current_page - 1) * meta.per_page + 1}</span> to{' '}
            <span className="font-medium">{Math.min(meta.current_page * meta.per_page, meta.total)}</span> of{' '}
            <span className="font-medium">{meta.total}</span> connections
          </p>
          <div className="flex gap-2">
            <Button
              variant="secondary"
              size="sm"
              disabled={meta.current_page <= 1}
              onClick={() => updateSearchParams(filters, meta.current_page - 1, sort)}
            >
              Previous
            </Button>
            <Button
              variant="secondary"
              size="sm"
              disabled={meta.current_page >= meta.last_page}
              onClick={() => updateSearchParams(filters, meta.current_page + 1, sort)}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};
