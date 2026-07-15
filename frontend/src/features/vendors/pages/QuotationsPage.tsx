import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { QuotationComparisonTable } from '../components/QuotationComparisonTable';
import { QuotationForm } from '../components/QuotationForm';
import { useVendorQuotations } from '../api/useVendors';
import { usePermissions } from '@/hooks/usePermissions';

export const QuotationsPage = () => {
  const [statusFilter, setStatusFilter] = useState('');
  const { data: quotations = [], isLoading } = useVendorQuotations();
  const { can } = usePermissions();
  const canManage = can('vendors.create') || can('vendors.update');

  const [isModalOpen, setModalOpen] = useState(false);

  const filteredQuotations = quotations.filter((q) => {
    if (!statusFilter) return true;
    return q.status === statusFilter;
  });

  return (
    <div className="space-y-6 p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Supplier Quotations & RFQ Comparisons</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Compare price bids, validity timelines, and item breakdown across supplier RFQ responses.
          </p>
        </div>

        {canManage && <Button size="sm" onClick={() => setModalOpen(true)}>+ Record New Quotation</Button>}
      </div>

      <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <label className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Filter Status:</label>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Quotations</option>
          <option value="received">Received</option>
          <option value="under_review">Under Review</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <QuotationComparisonTable quotations={filteredQuotations} isLoading={isLoading} canManage={canManage} />

      <QuotationForm isOpen={isModalOpen} onClose={() => setModalOpen(false)} />
    </div>
  );
};
