import { useCallback, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { useAuth } from '@/hooks/useAuth';

import { createPopSite, deletePopSite, getPopSites, changePopSiteStatus } from '../api/infrastructureApi';
import { PopSiteTable } from '../components/PopSiteTable';
import type { PopSite, PopSiteListFilters } from '../types';

export const PopSitesListPage = () => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const { user } = useAuth();

  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const perPage = 15;
  const sort = searchParams.get('sort') ?? '-created_at';

  const [filters, setFilters] = useState<PopSiteListFilters>({
    status: (searchParams.get('status') as PopSiteListFilters['status']) || undefined,
    city: searchParams.get('city') || undefined,
    region: searchParams.get('region') || undefined,
    power_status: (searchParams.get('power_status') as PopSiteListFilters['power_status']) || undefined,
    search: searchParams.get('search') || undefined,
  });

  const updateSearchParams = useCallback(
    (newFilters: PopSiteListFilters, newPage: number, newSort: string) => {
      const params = new URLSearchParams();
      if (newPage > 1) params.set('page', String(newPage));
      if (newSort !== '-created_at') params.set('sort', newSort);
      if (newFilters.status) params.set('status', newFilters.status);
      if (newFilters.city) params.set('city', newFilters.city);
      if (newFilters.region) params.set('region', newFilters.region);
      if (newFilters.power_status) params.set('power_status', newFilters.power_status);
      if (newFilters.search) params.set('search', newFilters.search);
      setSearchParams(params, { replace: true });
    },
    [setSearchParams],
  );

  const handleFiltersChange = (newFilters: PopSiteListFilters) => {
    setFilters(newFilters);
    updateSearchParams(newFilters, 1, sort);
  };

  const handleSortChange = (newSort: string) => {
    updateSearchParams(filters, page, newSort);
  };

  const handlePageChange = (newPage: number) => {
    updateSearchParams(filters, newPage, sort);
  };

  const popSitesQuery = useQuery({
    queryKey: ['popSites', page, perPage, filters, sort],
    queryFn: () => getPopSites({ ...filters, page, perPage, sort }),
    staleTime: 30_000,
  });

  const deleteMutation = useMutation({
    mutationFn: deletePopSite,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['popSites'] });
    },
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => changePopSiteStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['popSites'] });
    },
  });

  const handleDelete = (popSite: PopSite) => {
    if (confirm(`Are you sure you want to delete ${popSite.name}?`)) {
      deleteMutation.mutate(popSite.id);
    }
  };

  const handleStatusChange = (popSite: PopSite, newStatus: string) => {
    statusMutation.mutate({ id: popSite.id, status: newStatus });
  };

  const canCreate = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.create') || false;
  const canUpdate = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.update') || false;
  const canDelete = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.delete') || false;
  const canManage = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.manage') || false;

  const popSites = popSitesQuery.data?.data.map((d) => d.attributes) ?? [];
  const meta = popSitesQuery.data?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">POP Sites</h1>
          <p className="mt-1 text-sm text-slate-500">Manage Points of Presence for your network infrastructure.</p>
        </div>
        {canCreate && (
          <Button onClick={() => navigate('/network/infrastructure/pop-sites/new')}>
            Add POP Site
          </Button>
        )}
      </div>

      {popSitesQuery.error && (
        <Alert title="Failed to load POP sites" variant="danger">
          {apiErrorMessage(popSitesQuery.error, 'Unable to load POP sites. Please try again.')}
        </Alert>
      )}

      {deleteMutation.error && (
        <Alert title="Failed to delete POP site" variant="danger">
          {apiErrorMessage(deleteMutation.error, 'Unable to delete POP site. Please try again.')}
        </Alert>
      )}

      {/* Desktop Table */}
      <div className="hidden md:block">
        <PopSiteTable
          popSites={popSites}
          isLoading={popSitesQuery.isLoading}
          sort={sort}
          onSortChange={handleSortChange}
          onDelete={canDelete ? handleDelete : undefined}
          canUpdate={canUpdate}
          canDelete={canDelete}
        />
      </div>

      {/* Mobile Cards */}
      <div className="grid gap-4 md:hidden">
        {popSitesQuery.isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-32 animate-pulse rounded-xl bg-slate-100" />
          ))
        ) : (
          popSites.map((popSite) => (
            <div
              key={popSite.id}
              className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
              onClick={() => navigate(`/network/infrastructure/pop-sites/${popSite.id}`)}
            >
              <div className="flex items-start justify-between">
                <div>
                  <p className="font-medium text-slate-900">{popSite.name}</p>
                  <p className="text-sm text-slate-500">{popSite.code}</p>
                  {popSite.city && <p className="text-xs text-slate-400">{popSite.city}, {popSite.region}</p>}
                </div>
                <div className="flex items-center gap-2">
                  <span className={`px-2 py-1 text-xs rounded-full ${
                    popSite.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                    popSite.status === 'maintenance' ? 'bg-amber-100 text-amber-700' :
                    popSite.status === 'planning' ? 'bg-slate-100 text-slate-700' :
                    'bg-red-100 text-red-700'
                  }`}>
                    {popSite.status.charAt(0).toUpperCase() + popSite.status.slice(1)}
                  </span>
                </div>
              </div>
              <div className="mt-3 flex items-center justify-end gap-2">
                {canUpdate && (
                  <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); navigate(`/network/infrastructure/pop-sites/${popSite.id}/edit`); }}>
                    Edit
                  </Button>
                )}
                {canDelete && (
                  <Button variant="danger" size="sm" onClick={(e) => { e.stopPropagation(); handleDelete(popSite); }}>
                    Delete
                  </Button>
                )}
              </div>
            </div>
          ))
        )}
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <p className="text-sm text-slate-500">
            Showing <span className="font-medium">{(meta.current_page - 1) * meta.per_page + 1}</span> to{' '}
            <span className="font-medium">{Math.min(meta.current_page * meta.per_page, meta.total)}</span> of{' '}
            <span className="font-medium">{meta.total}</span> POP sites
          </p>
          <div className="flex gap-2">
            <Button
              variant="secondary"
              size="sm"
              disabled={meta.current_page <= 1}
              onClick={() => handlePageChange(meta.current_page - 1)}
            >
              Previous
            </Button>
            <Button
              variant="secondary"
              size="sm"
              disabled={meta.current_page >= meta.last_page}
              onClick={() => handlePageChange(meta.current_page + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};
