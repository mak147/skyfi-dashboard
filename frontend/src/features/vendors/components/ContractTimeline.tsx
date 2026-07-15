import React from 'react';
import { Button } from '@/components/ui/button';
import { SupplierStatusBadge } from './SupplierStatusBadge';
import { useDeleteVendorContract } from '../api/useVendors';
import type { VendorContract } from '../types';

interface ContractTimelineProps {
  contracts: VendorContract[];
  isLoading?: boolean;
  canManage?: boolean;
  onEdit?: (contract: VendorContract) => void;
}

export const ContractTimeline: React.FC<ContractTimelineProps> = ({ contracts, isLoading, canManage, onEdit }) => {
  const deleteMutation = useDeleteVendorContract();

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[...Array<number>(3)].map((_, i) => (
          <div key={i} className="h-24 animate-pulse rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" />
        ))}
      </div>
    );
  }

  if (contracts.length === 0) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        No active or historical contracts recorded for this supplier yet.
      </div>
    );
  }

  return (
    <div className="relative border-l border-slate-200 ml-4 space-y-6 pl-6 py-2 dark:border-slate-800">
      {contracts.map((contract) => {
        const isExpiring = contract.status === 'expiring';
        const isExpired = contract.status === 'expired';

        return (
          <div key={contract.id} className="relative group">
            {/* Timeline Dot */}
            <span
              className={`absolute -left-[31px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-slate-950 ${
                isExpired ? 'bg-red-500' : isExpiring ? 'bg-orange-500' : 'bg-emerald-500'
              }`}
            />

            <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
              <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <div className="flex items-center gap-2.5">
                    <h4 className="font-bold text-slate-800 dark:text-slate-100">{contract.title}</h4>
                    <SupplierStatusBadge status={contract.status} />
                  </div>
                  <div className="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                    Contract #{contract.contract_number} | {contract.vendor_name ? `Vendor: ${contract.vendor_name}` : `ID: ${contract.id}`}
                  </div>
                </div>

                <div className="text-right">
                  <div className="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                    {contract.currency} {Number(contract.contract_value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                  </div>
                  <div className="text-xs text-slate-500 dark:text-slate-400">
                    Duration: {contract.start_date} to {contract.end_date}
                  </div>
                </div>
              </div>

              {contract.renewal_date && (
                <div className="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-amber-50/80 px-2.5 py-1 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                  <span>📅 Renewal Due:</span>
                  <span className="font-semibold">{contract.renewal_date}</span>
                </div>
              )}

              {contract.notes && (
                <p className="mt-3 text-xs text-slate-600 border-t border-slate-100 pt-2.5 dark:border-slate-800 dark:text-slate-300">
                  {contract.notes}
                </p>
              )}

              {canManage && (
                <div className="mt-3 flex justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                  {onEdit && (
                    <Button variant="secondary" size="sm" onClick={() => onEdit(contract)}>
                      Edit Terms
                    </Button>
                  )}
                  <Button
                    variant="secondary"
                    size="sm"
                    className="text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40"
                    onClick={() => {
                      if (window.confirm(`Delete contract ${contract.contract_number}?`)) {
                        deleteMutation.mutate(contract.id);
                      }
                    }}
                  >
                    Remove
                  </Button>
                </div>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
};
