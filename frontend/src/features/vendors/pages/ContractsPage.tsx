import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ContractTimeline } from '../components/ContractTimeline';
import { ContractForm } from '../components/ContractForm';
import { useVendorContracts } from '../api/useVendors';
import { usePermissions } from '@/hooks/usePermissions';
import type { VendorContract } from '../types';

export const ContractsPage = () => {
  const [statusFilter, setStatusFilter] = useState('');
  const { data: contracts = [], isLoading } = useVendorContracts();
  const { can } = usePermissions();
  const canManage = can('vendors.contracts');

  const [isModalOpen, setModalOpen] = useState(false);
  const [editingContract, setEditingContract] = useState<VendorContract | null>(null);

  const filteredContracts = contracts.filter((c) => {
    if (!statusFilter) return true;
    return c.status === statusFilter;
  });

  return (
    <div className="space-y-6 p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Procurement & SLA Contracts</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Track hardware warranties, master service agreements, and renewal notification deadlines across suppliers.
          </p>
        </div>

        {canManage && (
          <Button
            size="sm"
            onClick={() => {
              setEditingContract(null);
              setModalOpen(true);
            }}
          >
            + Register New Contract
          </Button>
        )}
      </div>

      <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <label className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Filter by Status:</label>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">All Contracts</option>
          <option value="active">Active</option>
          <option value="expiring">Expiring Soon (Alerts)</option>
          <option value="expired">Expired</option>
          <option value="terminated">Terminated</option>
        </select>
      </div>

      <ContractTimeline
        contracts={filteredContracts}
        isLoading={isLoading}
        canManage={canManage}
        onEdit={(c) => {
          setEditingContract(c);
          setModalOpen(true);
        }}
      />

      <ContractForm
        initialData={editingContract}
        isOpen={isModalOpen}
        onClose={() => {
          setModalOpen(false);
          setEditingContract(null);
        }}
      />
    </div>
  );
};
