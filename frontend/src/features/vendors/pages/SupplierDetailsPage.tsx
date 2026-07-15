import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { SupplierStatusBadge } from '../components/SupplierStatusBadge';
import { PerformanceCards } from '../components/PerformanceCards';
import { SupplierStatistics } from '../components/SupplierStatistics';
import { ContactTable } from '../components/ContactTable';
import { ContactForm } from '../components/ContactForm';
import { ContractTimeline } from '../components/ContractTimeline';
import { ContractForm } from '../components/ContractForm';
import { QuotationComparisonTable } from '../components/QuotationComparisonTable';
import { QuotationForm } from '../components/QuotationForm';
import { RatingModal } from '../components/RatingModal';
import { SupplierForm } from '../components/SupplierForm';
import {
  useVendor,
  useVendorContacts,
  useVendorContracts,
  useVendorPurchasingHistory,
  useVendorQuotations,
} from '../api/useVendors';
import { usePermissions } from '@/hooks/usePermissions';
import type { VendorContact, VendorContract } from '../types';

export const SupplierDetailsPage = () => {
  const { id } = useParams<{ id: string }>();
  const vendorId = Number(id || 0);

  const [activeTab, setActiveTab] = useState<'overview' | 'contacts' | 'contracts' | 'quotations' | 'history'>('overview');
  const [isEditSupplierOpen, setEditSupplierOpen] = useState(false);
  const [isContactModalOpen, setContactModalOpen] = useState(false);
  const [editingContact, setEditingContact] = useState<VendorContact | null>(null);
  const [isContractModalOpen, setContractModalOpen] = useState(false);
  const [editingContract, setEditingContract] = useState<VendorContract | null>(null);
  const [isQuotationModalOpen, setQuotationModalOpen] = useState(false);
  const [isRatingModalOpen, setRatingModalOpen] = useState(false);

  const { data: supplier, isLoading: isVendorLoading } = useVendor(vendorId);
  const { data: contacts = [], isLoading: isContactsLoading } = useVendorContacts(vendorId);
  const { data: contracts = [], isLoading: isContractsLoading } = useVendorContracts(vendorId);
  const { data: quotations = [], isLoading: isQuotationsLoading } = useVendorQuotations(vendorId);
  const { data: history, isLoading: isHistoryLoading } = useVendorPurchasingHistory(vendorId);

  const { can } = usePermissions();
  const canManage = can('vendors.update') || can('vendors.manage');

  if (isVendorLoading || !supplier) {
    return (
      <div className="space-y-6 p-6">
        <div className="h-28 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800" />
        <div className="h-64 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800" />
      </div>
    );
  }

  return (
    <div className="space-y-6 p-6">
      {/* Back navigation & Header Card */}
      <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <Link to="/purchasing/vendors/list" className="hover:text-indigo-600 dark:hover:text-indigo-400">
          ← Back to Suppliers Directory
        </Link>
        <span>/</span>
        <span className="font-semibold text-slate-800 dark:text-slate-200">{supplier.name}</span>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-3">
              <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">{supplier.name}</h1>
              <SupplierStatusBadge status={supplier.status} />
              <span className="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 capitalize dark:bg-slate-800 dark:text-slate-300">
                {supplier.category || 'Hardware'}
              </span>
            </div>

            <div className="mt-2 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
              <div>
                Code: <span className="font-semibold text-slate-700 dark:text-slate-300">{supplier.code}</span>
              </div>
              {supplier.tax_id && (
                <div>
                  Tax Number: <span className="font-semibold text-slate-700 dark:text-slate-300">{supplier.tax_id}</span>
                </div>
              )}
              {supplier.registration_number && (
                <div>
                  Registration: <span className="font-semibold text-slate-700 dark:text-slate-300">{supplier.registration_number}</span>
                </div>
              )}
              <div>
                Payment Terms:{' '}
                <span className="font-semibold text-slate-700 dark:text-slate-300">
                  {supplier.payment_terms || 'Net 30'} ({supplier.currency || 'PKR'})
                </span>
              </div>
            </div>

            {(supplier.address || supplier.city) && (
              <div className="mt-2 text-xs text-slate-400 dark:text-slate-500">
                📍 {supplier.address ? `${supplier.address}, ` : ''}{supplier.city ? `${supplier.city}, ` : ''}{supplier.country}
              </div>
            )}
          </div>

          <div className="flex flex-wrap gap-2.5">
            {canManage && (
              <>
                <Button variant="secondary" size="sm" onClick={() => setRatingModalOpen(true)}>
                  ★ Evaluate Performance
                </Button>
                <Button variant="secondary" size="sm" onClick={() => setEditSupplierOpen(true)}>
                  Edit Profile
                </Button>
              </>
            )}
          </div>
        </div>

        {supplier.notes && (
          <div className="mt-4 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-600 border border-slate-100 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-300">
            <span className="font-semibold text-slate-700 dark:text-slate-200">Remarks / Terms Notes: </span>
            {supplier.notes}
          </div>
        )}
      </div>

      {/* Tabs */}
      <div className="border-b border-slate-200 dark:border-slate-800">
        <nav className="flex gap-8 text-sm font-semibold">
          {[
            { id: 'overview', label: 'Overview & Performance Scorecard' },
            { id: 'contacts', label: `Contacts (${contacts.length})` },
            { id: 'contracts', label: `Contracts (${contracts.length})` },
            { id: 'quotations', label: `Quotations (${quotations.length})` },
            { id: 'history', label: 'Purchasing & Inventory History' },
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as typeof activeTab)}
              className={`pb-3 border-b-2 transition ${
                activeTab === tab.id
                  ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                  : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content 1: Overview & Scorecard */}
      {activeTab === 'overview' && (
        <div className="space-y-6">
          <div>
            <h3 className="text-base font-bold text-slate-800 dark:text-slate-100 mb-3">Performance & Fulfillment KPIs</h3>
            <PerformanceCards metrics={supplier.performance_metrics} />
          </div>

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h4 className="font-bold text-slate-800 dark:text-slate-100">Primary Contact Summary</h4>
                <Button variant="secondary" size="sm" onClick={() => setActiveTab('contacts')}>
                  Manage Contacts →
                </Button>
              </div>
              <div className="mt-4 space-y-3">
                {contacts.length === 0 ? (
                  <div className="text-sm text-slate-400 dark:text-slate-500">No contacts listed for this supplier.</div>
                ) : (
                  contacts
                    .filter((c) => Boolean(c.is_primary) || contacts.indexOf(c) === 0)
                    .map((c) => (
                      <div key={c.id} className="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60">
                        <div className="font-bold text-slate-800 dark:text-slate-200">
                          {c.first_name} {c.last_name} ({c.position || 'Representative'})
                        </div>
                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                          {c.email && `✉ ${c.email}`} | {c.phone && `📞 ${c.phone}`}
                        </div>
                      </div>
                    ))
                )}
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h4 className="font-bold text-slate-800 dark:text-slate-100">Active Contracts Summary</h4>
                <Button variant="secondary" size="sm" onClick={() => setActiveTab('contracts')}>
                  Manage Contracts →
                </Button>
              </div>
              <div className="mt-4 space-y-3">
                {contracts.length === 0 ? (
                  <div className="text-sm text-slate-400 dark:text-slate-500">No contracts registered.</div>
                ) : (
                  contracts.slice(0, 2).map((c) => (
                    <div key={c.id} className="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60">
                      <div className="flex items-center justify-between">
                        <span className="font-bold text-slate-800 dark:text-slate-200">{c.title}</span>
                        <SupplierStatusBadge status={c.status} />
                      </div>
                      <div className="mt-1 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Contract #{c.contract_number}</span>
                        <span className="font-semibold text-indigo-600 dark:text-indigo-400">
                          {c.currency} {Number(c.contract_value).toLocaleString()}
                        </span>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tab Content 2: Contacts */}
      {activeTab === 'contacts' && (
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">Supplier Contacts & Departments</h3>
            {canManage && (
              <Button
                size="sm"
                onClick={() => {
                  setEditingContact(null);
                  setContactModalOpen(true);
                }}
              >
                + Add Contact
              </Button>
            )}
          </div>
          <ContactTable
            contacts={contacts}
            isLoading={isContactsLoading}
            canManage={canManage}
            onEdit={(c) => {
              setEditingContact(c);
              setContactModalOpen(true);
            }}
          />
        </div>
      )}

      {/* Tab Content 3: Contracts */}
      {activeTab === 'contracts' && (
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">Agreements, SLAs & Expiration Timeline</h3>
            {can('vendors.contracts') && (
              <Button
                size="sm"
                onClick={() => {
                  setEditingContract(null);
                  setContractModalOpen(true);
                }}
              >
                + Register Contract
              </Button>
            )}
          </div>
          <ContractTimeline
            contracts={contracts}
            isLoading={isContractsLoading}
            canManage={can('vendors.contracts')}
            onEdit={(c) => {
              setEditingContract(c);
              setContractModalOpen(true);
            }}
          />
        </div>
      )}

      {/* Tab Content 4: Quotations */}
      {activeTab === 'quotations' && (
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">Quotations & RFQ History</h3>
            {canManage && <Button size="sm" onClick={() => setQuotationModalOpen(true)}>+ Record Quotation</Button>}
          </div>
          <QuotationComparisonTable quotations={quotations} isLoading={isQuotationsLoading} canManage={canManage} />
        </div>
      )}

      {/* Tab Content 5: Purchasing History */}
      {activeTab === 'history' && (
        <div className="space-y-6">
          <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">Purchasing, Financial & Inventory Integration History</h3>
          <SupplierStatistics history={history} isLoading={isHistoryLoading} />

          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h4 className="font-bold text-slate-800 dark:text-slate-100 mb-4">Linked Purchase Orders ({history?.purchase_orders.length || 0})</h4>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                  <tr>
                    <th className="px-3 py-2.5">PO Number</th>
                    <th className="px-3 py-2.5">Order Date</th>
                    <th className="px-3 py-2.5 text-right">Total Amount</th>
                    <th className="px-3 py-2.5 text-center">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                  {!history || history.purchase_orders.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                        No purchase orders linked yet.
                      </td>
                    </tr>
                  ) : (
                    history.purchase_orders.map((po) => (
                      <tr key={po.id} className="transition hover:bg-slate-50/75 dark:hover:bg-slate-800/40">
                        <td className="px-3 py-2.5 font-semibold text-indigo-600 dark:text-indigo-400">{po.po_number}</td>
                        <td className="px-3 py-2.5 text-slate-600 dark:text-slate-300">{po.order_date}</td>
                        <td className="px-3 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200">
                          {po.currency} {Number(po.total_amount).toLocaleString()}
                        </td>
                        <td className="px-3 py-2.5 text-center">
                          <SupplierStatusBadge status={po.status} />
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Modals */}
      <SupplierForm initialData={supplier} isOpen={isEditSupplierOpen} onClose={() => setEditSupplierOpen(false)} />
      <ContactForm vendorId={vendorId} initialData={editingContact} isOpen={isContactModalOpen} onClose={() => { setContactModalOpen(false); setEditingContact(null); }} />
      <ContractForm vendorId={vendorId} initialData={editingContract} isOpen={isContractModalOpen} onClose={() => { setContractModalOpen(false); setEditingContract(null); }} />
      <QuotationForm vendorId={vendorId} isOpen={isQuotationModalOpen} onClose={() => setQuotationModalOpen(false)} />
      <RatingModal vendorId={vendorId} isOpen={isRatingModalOpen} onClose={() => setRatingModalOpen(false)} />
    </div>
  );
};
