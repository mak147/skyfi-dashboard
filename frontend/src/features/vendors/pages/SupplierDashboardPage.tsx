import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { SupplierForm } from '../components/SupplierForm';
import { ContactForm } from '../components/ContactForm';
import { ContractForm } from '../components/ContractForm';
import { QuotationForm } from '../components/QuotationForm';
import { SupplierStatusBadge } from '../components/SupplierStatusBadge';
import { useVendorsDashboard } from '../api/useVendors';
import { usePermissions } from '@/hooks/usePermissions';

export const SupplierDashboardPage = () => {
  const { data: dashboard, isLoading, error } = useVendorsDashboard();
  const { can } = usePermissions();
  const canManage = can('vendors.create') || can('vendors.manage');

  const [isSupplierModalOpen, setSupplierModalOpen] = useState(false);
  const [isContactModalOpen, setContactModalOpen] = useState(false);
  const [isContractModalOpen, setContractModalOpen] = useState(false);
  const [isQuotationModalOpen, setQuotationModalOpen] = useState(false);

  if (isLoading) {
    return (
      <div className="space-y-6 p-6">
        <div className="h-10 w-64 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-800" />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {[...Array<number>(4)].map((_, i) => (
            <div key={i} className="h-32 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
          ))}
        </div>
      </div>
    );
  }

  if (error || !dashboard) {
    return (
      <div className="p-6">
        <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
          Failed to load vendor management dashboard metrics.
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 p-6">
      {/* Header & Quick Actions */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Vendor & Supplier Management</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Monitor supplier performance, procurement contracts, RFQ quotations, and catalog purchasing relationships.
          </p>
        </div>

        {canManage && (
          <div className="flex flex-wrap gap-2.5">
            <Button size="sm" onClick={() => setSupplierModalOpen(true)}>
              + New Supplier
            </Button>
            <Button size="sm" variant="secondary" onClick={() => setContactModalOpen(true)}>
              + Add Contact
            </Button>
            <Button size="sm" variant="secondary" onClick={() => setContractModalOpen(true)}>
              + Register Contract
            </Button>
            <Button size="sm" variant="secondary" onClick={() => setQuotationModalOpen(true)}>
              + Record Quotation
            </Button>
          </div>
        )}
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Suppliers</span>
            <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
              Directory
            </span>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-bold text-slate-800 dark:text-slate-100">{dashboard.active_suppliers}</span>
            <span className="text-xs text-slate-400 dark:text-slate-500">of {dashboard.total_suppliers} total</span>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Expiring Contracts</span>
            <span
              className={`rounded-full px-2 py-0.5 text-xs font-bold ${
                dashboard.expiring_contracts_count > 0
                  ? 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300'
                  : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
              }`}
            >
              Alerts
            </span>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span
              className={`text-3xl font-bold ${
                dashboard.expiring_contracts_count > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-slate-800 dark:text-slate-100'
              }`}
            >
              {dashboard.expiring_contracts_count}
            </span>
            <span className="text-xs text-slate-400 dark:text-slate-500">within 60 days</span>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Average Rating</span>
            <span className="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
              Score
            </span>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-bold text-amber-600 dark:text-amber-400">{dashboard.average_supplier_rating.toFixed(2)}</span>
            <span className="text-xs text-slate-400 dark:text-slate-500">/ 5.00</span>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Procurement Spend</span>
            <span className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
              Purchasing
            </span>
          </div>
          <div className="mt-4 flex items-baseline gap-1">
            <span className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
              PKR {Number(dashboard.total_procurement_spend).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}
            </span>
          </div>
        </div>
      </div>

      {/* Main Sections Grid */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Top Suppliers by Procurement Spend */}
        <div className="col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
              <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">Top Suppliers by Procurement Spend</h3>
              <p className="text-xs text-slate-500 dark:text-slate-400">Highest volume purchasing relationships</p>
            </div>
            <Link to="/purchasing/vendors/list" className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
              View All Suppliers →
            </Link>
          </div>

          <div className="mt-4 overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                <tr>
                  <th className="px-3 py-2.5">Supplier</th>
                  <th className="px-3 py-2.5">Category</th>
                  <th className="px-3 py-2.5 text-center">Rating</th>
                  <th className="px-3 py-2.5 text-right">Orders</th>
                  <th className="px-3 py-2.5 text-right">Total Spend</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {dashboard.top_suppliers.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                      No purchase orders recorded for suppliers yet.
                    </td>
                  </tr>
                ) : (
                  dashboard.top_suppliers.map((item) => (
                    <tr key={item.id} className="transition hover:bg-slate-50/75 dark:hover:bg-slate-800/40">
                      <td className="px-3 py-2.5">
                        <Link
                          to={`/purchasing/vendors/${item.id}`}
                          className="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                          {item.name}
                        </Link>
                        <div className="text-xs text-slate-400 dark:text-slate-500">Code: {item.code}</div>
                      </td>
                      <td className="px-3 py-2.5 capitalize text-slate-600 dark:text-slate-300">{item.category}</td>
                      <td className="px-3 py-2.5 text-center font-medium text-amber-600 dark:text-amber-400">
                        ★ {Number(item.overall_rating || 5.0).toFixed(2)}
                      </td>
                      <td className="px-3 py-2.5 text-right text-slate-700 dark:text-slate-300">{item.po_count}</td>
                      <td className="px-3 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200">
                        PKR {Number(item.total_spend).toLocaleString()}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Expiring Contracts Alerts */}
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
              <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">Expiring Contracts</h3>
              <p className="text-xs text-slate-500 dark:text-slate-400">Contracts due for renewal within 60 days</p>
            </div>
            <Link to="/purchasing/vendors/contracts" className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
              View Contracts →
            </Link>
          </div>

          <div className="mt-4 space-y-3">
            {dashboard.expiring_contracts_list.length === 0 ? (
              <div className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">No expiring contracts right now. All clear! ✓</div>
            ) : (
              dashboard.expiring_contracts_list.map((c) => (
                <div key={c.id} className="rounded-xl border border-slate-100 bg-slate-50 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                  <div className="flex items-center justify-between">
                    <span className="font-semibold text-slate-800 dark:text-slate-200 truncate">{c.title}</span>
                    <SupplierStatusBadge status={c.status} />
                  </div>
                  <div className="mt-1 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>{c.vendor_name} ({c.contract_number})</span>
                    <span className="font-medium text-red-600 dark:text-red-400">Ends: {c.end_date}</span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Modals */}
      <SupplierForm isOpen={isSupplierModalOpen} onClose={() => setSupplierModalOpen(false)} />
      <ContactForm isOpen={isContactModalOpen} onClose={() => setContactModalOpen(false)} />
      <ContractForm isOpen={isContractModalOpen} onClose={() => setContractModalOpen(false)} />
      <QuotationForm isOpen={isQuotationModalOpen} onClose={() => setQuotationModalOpen(false)} />
    </div>
  );
};
