import React from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { SupplierStatusBadge } from './SupplierStatusBadge';
import { useActivateVendor, useArchiveVendor } from '../api/useVendors';
import type { Vendor } from '../types';

interface SupplierTableProps {
  suppliers: Vendor[];
  isLoading?: boolean;
  canManage?: boolean;
  onEdit?: (supplier: Vendor) => void;
}

export const SupplierTable: React.FC<SupplierTableProps> = ({ suppliers, isLoading, canManage, onEdit }) => {
  const archiveMutation = useArchiveVendor();
  const activateMutation = useActivateVendor();

  if (isLoading) {
    return (
      <div className="space-y-3">
        {[...Array<number>(5)].map((_, i) => (
          <div key={i} className="h-16 animate-pulse rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" />
        ))}
      </div>
    );
  }

  if (suppliers.length === 0) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        No suppliers or vendors found matching your criteria.
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
            <tr>
              <th className="px-4 py-3.5">Company Name & Code</th>
              <th className="px-4 py-3.5">Category & Terms</th>
              <th className="px-4 py-3.5">Contact Person</th>
              <th className="px-4 py-3.5">Rating & Summary</th>
              <th className="px-4 py-3.5 text-center">Status</th>
              <th className="px-4 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
            {suppliers.map((supplier) => {
              const rating = Number(supplier.overall_rating || 0);

              return (
                <tr key={supplier.id} className="transition hover:bg-slate-50/75 dark:hover:bg-slate-800/40">
                  <td className="px-4 py-3.5">
                    <Link
                      to={`/purchasing/vendors/${supplier.id}`}
                      className="font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                      {supplier.name}
                    </Link>
                    <div className="text-xs text-slate-400 dark:text-slate-500">
                      Code: <span className="font-semibold text-slate-600 dark:text-slate-300">{supplier.code}</span>
                      {supplier.tax_id ? ` | Tax ID: ${supplier.tax_id}` : ''}
                    </div>
                  </td>

                  <td className="px-4 py-3.5">
                    <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 capitalize dark:bg-slate-800 dark:text-slate-300">
                      {supplier.category || 'Hardware'}
                    </span>
                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                      Terms: {supplier.payment_terms || 'Net 30'} ({supplier.currency || 'PKR'})
                    </div>
                  </td>

                  <td className="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                    <div className="font-medium text-slate-800 dark:text-slate-200">{supplier.contact_name || 'N/A'}</div>
                    {supplier.email && <div className="text-xs text-slate-500 dark:text-slate-400">✉ {supplier.email}</div>}
                    {supplier.phone && <div className="text-xs text-slate-500 dark:text-slate-400">📞 {supplier.phone}</div>}
                  </td>

                  <td className="px-4 py-3.5">
                    <div className="flex items-center gap-1 text-amber-500 font-semibold text-xs">
                      <span>★ {rating.toFixed(2)} / 5.0</span>
                    </div>
                    <div className="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                      {supplier.contacts_count || 0} Contacts | {supplier.contracts_count || 0} Contracts
                    </div>
                  </td>

                  <td className="px-4 py-3.5 text-center">
                    <SupplierStatusBadge status={supplier.status} />
                  </td>

                  <td className="px-4 py-3.5 text-right">
                    <div className="flex justify-end gap-1.5">
                      <Link to={`/purchasing/vendors/${supplier.id}`}>
                        <Button variant="secondary" size="sm">
                          View
                        </Button>
                      </Link>

                      {canManage && onEdit && (
                        <Button variant="secondary" size="sm" onClick={() => onEdit(supplier)}>
                          Edit
                        </Button>
                      )}

                      {canManage && supplier.status === 'active' && (
                        <Button
                          variant="secondary"
                          size="sm"
                          className="text-amber-700 border-amber-200 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-300 dark:hover:bg-amber-950/40"
                          onClick={() => {
                            if (window.confirm(`Archive supplier ${supplier.name}?`)) {
                              archiveMutation.mutate(supplier.id);
                            }
                          }}
                        >
                          Archive
                        </Button>
                      )}

                      {canManage && supplier.status !== 'active' && (
                        <Button
                          variant="secondary"
                          size="sm"
                          className="text-emerald-700 border-emerald-200 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                          onClick={() => {
                            if (window.confirm(`Activate supplier ${supplier.name}?`)) {
                              activateMutation.mutate(supplier.id);
                            }
                          }}
                        >
                          Activate
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
};
