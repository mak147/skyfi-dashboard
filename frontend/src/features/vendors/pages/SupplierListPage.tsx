import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { SupplierTable } from '../components/SupplierTable';
import { SupplierForm } from '../components/SupplierForm';
import { useVendors } from '../api/useVendors';
import { usePermissions } from '@/hooks/usePermissions';
import type { Vendor } from '../types';

export const SupplierListPage = () => {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [minRatingFilter, setMinRatingFilter] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useVendors({
    search: search || undefined,
    status: statusFilter || undefined,
    category: categoryFilter || undefined,
    min_rating: minRatingFilter || undefined,
    page,
    per_page: 20,
  });

  const { can } = usePermissions();
  const canManage = can('vendors.create') || can('vendors.manage');

  const [isModalOpen, setModalOpen] = useState(false);
  const [editingSupplier, setEditingSupplier] = useState<Vendor | null>(null);

  const suppliers = data?.data.map((item) => item.attributes) || [];
  const meta = data?.meta || { current_page: 1, last_page: 1, total: 0, per_page: 20 };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Suppliers Directory</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Manage company profiles, payment terms, categories, contact persons, and ratings.
          </p>
        </div>

        {canManage && (
          <Button
            size="sm"
            onClick={() => {
              setEditingSupplier(null);
              setModalOpen(true);
            }}
          >
            + Register New Supplier
          </Button>
        )}
      </div>

      {/* Filter Bar */}
      <div className="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div className="flex-1 min-w-[200px]">
          <input
            type="text"
            placeholder="Search by name, code, email or contact person..."
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            className="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          />
        </div>

        <select
          value={statusFilter}
          onChange={(e) => {
            setStatusFilter(e.target.value);
            setPage(1);
          }}
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="on_hold">On Hold</option>
          <option value="inactive">Archived / Inactive</option>
        </select>

        <select
          value={categoryFilter}
          onChange={(e) => {
            setCategoryFilter(e.target.value);
            setPage(1);
          }}
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Categories</option>
          <option value="hardware">Hardware & Equipment</option>
          <option value="fiber_optics">Fiber & Infrastructure</option>
          <option value="software">Software & Licensing</option>
          <option value="contractor">Installation Contractor</option>
          <option value="bandwidth">Bandwidth & Transit Provider</option>
          <option value="general">General Supplies</option>
        </select>

        <select
          value={minRatingFilter}
          onChange={(e) => {
            setMinRatingFilter(e.target.value);
            setPage(1);
          }}
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">Any Rating</option>
          <option value="4.0">4.0+ Stars</option>
          <option value="4.5">4.5+ Stars</option>
        </select>
      </div>

      {/* Main Table */}
      <SupplierTable
        suppliers={suppliers}
        isLoading={isLoading}
        canManage={canManage}
        onEdit={(v) => {
          setEditingSupplier(v);
          setModalOpen(true);
        }}
      />

      {/* Pagination */}
      {!isLoading && meta.last_page > 1 && (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <span className="text-xs text-slate-500 dark:text-slate-400">
            Showing Page <span className="font-semibold">{meta.current_page}</span> of <span className="font-semibold">{meta.last_page}</span> ({meta.total}{' '}
            total suppliers)
          </span>

          <div className="flex gap-2">
            <Button variant="secondary" size="sm" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
              Previous
            </Button>
            <Button variant="secondary" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
              Next
            </Button>
          </div>
        </div>
      )}

      {/* Form Modal */}
      <SupplierForm
        initialData={editingSupplier}
        isOpen={isModalOpen}
        onClose={() => {
          setModalOpen(false);
          setEditingSupplier(null);
        }}
      />
    </div>
  );
};
