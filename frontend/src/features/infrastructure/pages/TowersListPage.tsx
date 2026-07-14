import { useCallback, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { useAuth } from '@/hooks/useAuth';

import { createTower, deleteTower, getTowers, changeTowerStatus } from '../api/infrastructureApi';
import { TowerTable } from '../components/TowerTable';
import type { Tower, TowerListFilters } from '../types';

export const TowersListPage = () => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const { user } = useAuth();

  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const perPage = 15;
  const sort = searchParams.get('sort') ?? '-created_at';

  const [filters, setFilters] = useState<TowerListFilters>({
    status: (searchParams.get('status') as TowerListFilters['status']) || undefined,
    tower_type: (searchParams.get('tower_type') as TowerListFilters['tower_type']) || undefined,
    pop_site_id: searchParams.get('pop_site_id') ? Number(searchParams.get('pop_site_id')) : undefined,
    city: searchParams.get('city') || undefined,
    region: searchParams.get('region') || undefined,
    search: searchParams.get('search') || undefined,
  });

  const updateSearchParams = useCallback(
    (newFilters: TowerListFilters, newPage: number, newSort: string) => {
      const params = new URLSearchParams();
      if (newPage > 1) params.set('page', String(newPage));
      if (newSort !== '-created_at') params.set('sort', newSort);
      if (newFilters.status) params.set('status', newFilters.status);
      if (newFilters.tower_type) params.set('tower_type', newFilters.tower_type);
      if (newFilters.pop_site_id) params.set('pop_site_id', String(newFilters.pop_site_id));
      if (newFilters.city) params.set('city', newFilters.city);
      if (newFilters.region) params.set('region', newFilters.region);
      if (newFilters.search) params.set('search', newFilters.search);
      setSearchParams(params, { replace: true });
    },
    [setSearchParams],
  );

  const handleFiltersChange = (newFilters: TowerListFilters) => {
    setFilters(newFilters);
    updateSearchParams(newFilters, 1, sort);
  };

  const handleSortChange = (newSort: string) => {
    updateSearchParams(filters, page, newSort);
  };

  const handlePageChange = (newPage: number) => {
    updateSearchParams(filters, newPage, sort);
  };

  const towersQuery = useQuery({
    queryKey: ['towers', page, perPage, filters, sort],
    queryFn: () => getTowers({ ...filters, page, perPage, sort }),
    staleTime: 30_000,
  });

  const deleteMutation = useMutation({
    mutationFn: deleteTower,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['towers'] });
    },
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => changeTowerStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['towers'] });
    },
  });

  const handleDelete = (tower: Tower) => {
    if (confirm(`Are you sure you want to delete ${tower.name}?`)) {
      deleteMutation.mutate(tower.id);
    }
  };

  const canCreate = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.create') || false;
  const canUpdate = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.update') || false;
  const canDelete = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.delete') || false;
  const canManage = user?.roles.includes('Super Administrator') || user?.permissions?.includes('infrastructure.manage') || false;

  const towers = towersQuery.data?.data.map((d) => d.attributes) ?? [];
  const meta = towersQuery.data?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Towers</h1>
          <p className="mt-1 text-sm text-slate-500">Manage tower sites and their equipment.</p>
        </div>
        {canCreate && (
          <Button onClick={() => navigate('/network/infrastructure/towers/new')}>
            Add Tower
          </Button>
        )}
      </div>

      {towersQuery.error && (
        <Alert title="Failed to load towers" variant="danger">
          {apiErrorMessage(towersQuery.error, 'Unable to load towers. Please try again.')}
        </Alert>
      )}

      {deleteMutation.error && (
        <Alert title="Failed to delete tower" variant="danger">
          {apiErrorMessage(deleteMutation.error, 'Unable to delete tower. Please try again.')}
        </Alert>
      )}

      {/* Desktop Table */}
      <div className="hidden md:block">
        <TowerTable
          towers={towers}
          isLoading={towersQuery.isLoading}
          sort={sort}
          onSortChange={handleSortChange}
          onDelete={canDelete ? handleDelete : undefined}
          canUpdate={canUpdate}
          canDelete={canDelete}
        />
      </div>

      {/* Mobile Cards */}
      <div className="grid gap-4 md:hidden">
        {towersQuery.isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-32 animate-pulse rounded-xl bg-slate-100" />
          ))
        ) : (
          towers.map((tower) => (
            <div
              key={tower.id}
              className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
              onClick={() => navigate(`/network/infrastructure/towers/${tower.id}`)}
            >
              <div className="flex items-start justify-between">
                <div>
                  <p className="font-medium text-slate-900">{tower.name}</p>
                  <p className="text-sm text-slate-500">{tower.code || 'No code'}</p>
                  <p className="text-xs text-slate-400 capitalize">{tower.tower_type.replace('_', ' ')} • {tower.height_meters || '?'}m</p>
                  {tower.pop_site_name && <p className="text-xs text-slate-400">{tower.pop_site_name}</p>}
                </div>
                <div className="flex items-center gap-2">
                  <span className={`px-2 py-1 text-xs rounded-full ${
                    tower.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                    tower.status === 'maintenance' ? 'bg-amber-100 text-amber-700' :
                    tower.status === 'planning' ? 'bg-slate-100 text-slate-700' :
                    'bg-red-100 text-red-700'
                  }`}>
                    {tower.status.charAt(0).toUpperCase() + tower.status.slice(1)}
                  </span>
                </div>
              </div>
              <div className="mt-3 flex items-center justify-end gap-2">
                {canUpdate && (
                  <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); navigate(`/network/infrastructure/towers/${tower.id}/edit`); }}>
                    Edit
                  </Button>
                )}
                {canDelete && (
                  <Button variant="danger" size="sm" onClick={(e) => { e.stopPropagation(); handleDelete(tower); }}>
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
            <span className="font-medium">{meta.total}</span> towers
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
