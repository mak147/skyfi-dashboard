import { useCallback, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import { useAuth } from '@/hooks/useAuth';

import { deleteCustomer, getCustomers } from '../api/customerApi';
import { CustomerCard } from '../components/CustomerCard';
import { CustomerFiltersBar } from '../components/CustomerFilters';
import { CustomerTable } from '../components/CustomerTable';
import type { Customer, CustomerFilters } from '../types';

export const CustomersListPage = () => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const { user } = useAuth();

  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const perPage = 15;
  const sort = searchParams.get('sort') ?? '-created_at';

  const [filters, setFilters] = useState<CustomerFilters>({
    status: (searchParams.get('status') as CustomerFilters['status']) || undefined,
    city: searchParams.get('city') || undefined,
    area: searchParams.get('area') || undefined,
    search: searchParams.get('search') || undefined,
  });

  const updateSearchParams = useCallback(
    (newFilters: CustomerFilters, newPage: number, newSort: string) => {
      const params = new URLSearchParams();
      if (newPage > 1) params.set('page', String(newPage));
      if (newSort !== '-created_at') params.set('sort', newSort);
      if (newFilters.status) params.set('status', newFilters.status);
      if (newFilters.city) params.set('city', newFilters.city);
      if (newFilters.area) params.set('area', newFilters.area);
      if (newFilters.search) params.set('search', newFilters.search);
      setSearchParams(params, { replace: true });
    },
    [setSearchParams],
  );

  const handleFiltersChange = (newFilters: CustomerFilters) => {
    setFilters(newFilters);
    updateSearchParams(newFilters, 1, sort);
  };

  const handleSortChange = (newSort: string) => {
    updateSearchParams(filters, page, newSort);
  };

  const handlePageChange = (newPage: number) => {
    updateSearchParams(filters, newPage, sort);
  };

  const customersQuery = useQuery({
    queryKey: ['customers', page, perPage, filters, sort],
    queryFn: () => getCustomers(page, perPage, filters, sort),
    staleTime: 30_000,
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCustomer,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });

  const handleDelete = (customer: Customer) => {
    if (confirm(`Are you sure you want to delete ${customer.full_name}?`)) {
      deleteMutation.mutate(customer.id);
    }
  };

  const canCreate = user?.roles.includes('Super Administrator') || false; // Simplified; real check would use permissions
  const canUpdate = user?.roles.includes('Super Administrator') || false;
  const canDelete = user?.roles.includes('Super Administrator') || false;

  const customers = customersQuery.data?.data.map((d) => d.attributes) ?? [];
  const meta = customersQuery.data?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Customers</h1>
          <p className="mt-1 text-sm text-slate-500">Manage your ISP customer lifecycle from leads to archived accounts.</p>
        </div>
        {canCreate && (
          <Button onClick={() => navigate('/customers/new')}>
            Add Customer
          </Button>
        )}
      </div>

      <CustomerFiltersBar filters={filters} onChange={handleFiltersChange} />

      {customersQuery.error && (
        <Alert title="Failed to load customers" variant="danger">
          {apiErrorMessage(customersQuery.error, 'Unable to load customers. Please try again.')}
        </Alert>
      )}

      {deleteMutation.error && (
        <Alert title="Failed to delete customer" variant="danger">
          {apiErrorMessage(deleteMutation.error, 'Unable to delete customer. Please try again.')}
        </Alert>
      )}

      {/* Desktop Table */}
      <div className="hidden md:block">
        <CustomerTable
          customers={customers}
          isLoading={customersQuery.isLoading}
          sort={sort}
          onSortChange={handleSortChange}
          onDelete={canDelete ? handleDelete : undefined}
          canUpdate={canUpdate}
          canDelete={canDelete}
        />
      </div>

      {/* Mobile Cards */}
      <div className="grid gap-4 md:hidden">
        {customersQuery.isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-32 animate-pulse rounded-xl bg-slate-100" />
          ))
        ) : (
          customers.map((customer) => (
            <CustomerCard
              key={customer.id}
              customer={customer}
              onDelete={canDelete ? handleDelete : undefined}
              canUpdate={canUpdate}
              canDelete={canDelete}
            />
          ))
        )}
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <p className="text-sm text-slate-500">
            Showing <span className="font-medium">{(meta.current_page - 1) * meta.per_page + 1}</span> to{' '}
            <span className="font-medium">{Math.min(meta.current_page * meta.per_page, meta.total)}</span> of{' '}
            <span className="font-medium">{meta.total}</span> customers
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
