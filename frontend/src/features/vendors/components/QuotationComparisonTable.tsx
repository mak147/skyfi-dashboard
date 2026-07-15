import React from 'react';
import { Button } from '@/components/ui/button';
import { SupplierStatusBadge } from './SupplierStatusBadge';
import { useDeleteVendorQuotation, useUpdateQuotationStatus } from '../api/useVendors';
import type { VendorQuotation } from '../types';

interface QuotationComparisonTableProps {
  quotations: VendorQuotation[];
  isLoading?: boolean;
  canManage?: boolean;
}

export const QuotationComparisonTable: React.FC<QuotationComparisonTableProps> = ({ quotations, isLoading, canManage }) => {
  const statusMutation = useUpdateQuotationStatus();
  const deleteMutation = useDeleteVendorQuotation();

  if (isLoading) {
    return (
      <div className="space-y-3">
        {[...Array<number>(3)].map((_, i) => (
          <div key={i} className="h-16 animate-pulse rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" />
        ))}
      </div>
    );
  }

  if (quotations.length === 0) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        No supplier quotations or RFQ responses found.
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
            <tr>
              <th className="px-4 py-3.5">Quotation / RFQ Ref</th>
              <th className="px-4 py-3.5">Supplier</th>
              <th className="px-4 py-3.5">Submission & Validity</th>
              <th className="px-4 py-3.5">Line Items Summary</th>
              <th className="px-4 py-3.5 text-right">Quoted Amount</th>
              <th className="px-4 py-3.5 text-center">Status</th>
              {canManage && <th className="px-4 py-3.5 text-right">Actions</th>}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
            {quotations.map((quotation) => (
              <tr key={quotation.id} className="transition hover:bg-slate-50/75 dark:hover:bg-slate-800/40">
                <td className="px-4 py-3.5 font-semibold text-slate-800 dark:text-slate-200">
                  {quotation.quotation_number}
                  <div className="text-xs font-normal text-slate-400 dark:text-slate-500">
                    RFQ: {quotation.rfq_number || quotation.purchase_request_number || 'Direct Quote'}
                  </div>
                </td>
                <td className="px-4 py-3.5 font-medium text-slate-800 dark:text-slate-200">
                  {quotation.vendor_name || `Supplier #${quotation.vendor_id}`}
                </td>
                <td className="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                  <div className="text-xs">Date: {quotation.quotation_date}</div>
                  <div className="text-xs font-medium text-amber-700 dark:text-amber-400">Valid to: {quotation.validity_date}</div>
                </td>
                <td className="px-4 py-3.5">
                  <div className="text-xs font-medium text-slate-700 dark:text-slate-300">
                    {quotation.item_count ?? (quotation.items?.length || 0)} Quoted Items
                  </div>
                  {quotation.items && quotation.items.length > 0 && (
                    <div className="mt-1 text-xs text-slate-400 truncate max-w-xs dark:text-slate-500">
                      {quotation.items[0].description} {quotation.items.length > 1 ? `(+${quotation.items.length - 1} more)` : ''}
                    </div>
                  )}
                </td>
                <td className="px-4 py-3.5 text-right font-bold text-slate-800 dark:text-slate-200">
                  {quotation.currency} {Number(quotation.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td className="px-4 py-3.5 text-center">
                  <SupplierStatusBadge status={quotation.status} />
                </td>
                {canManage && (
                  <td className="px-4 py-3.5 text-right">
                    <div className="flex justify-end gap-1.5">
                      {quotation.status !== 'accepted' && (
                        <Button
                          variant="secondary"
                          size="sm"
                          className="text-emerald-700 border-emerald-200 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                          onClick={() => statusMutation.mutate({ quotationId: quotation.id, status: 'accepted' })}
                        >
                          Accept
                        </Button>
                      )}
                      {quotation.status !== 'rejected' && (
                        <Button
                          variant="secondary"
                          size="sm"
                          className="text-red-700 border-red-200 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40"
                          onClick={() => statusMutation.mutate({ quotationId: quotation.id, status: 'rejected' })}
                        >
                          Reject
                        </Button>
                      )}
                      <Button
                        variant="secondary"
                        size="sm"
                        className="text-slate-500 hover:text-red-600"
                        onClick={() => {
                          if (window.confirm(`Delete quotation ${quotation.quotation_number}?`)) {
                            deleteMutation.mutate(quotation.id);
                          }
                        }}
                      >
                        ✕
                      </Button>
                    </div>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
