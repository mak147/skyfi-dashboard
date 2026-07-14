import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { clsx } from 'clsx';

import { Button } from '@/components/ui/button';

import type { Customer } from '../types';
import { CustomerStatusBadge } from './CustomerStatusBadge';

interface CustomerProfileProps {
  customer: Customer;
  canUpdate?: boolean;
  canManage?: boolean;
}

type TabKey = 'overview' | 'personal' | 'service' | 'billing' | 'payments' | 'tickets' | 'installation' | 'activity';

const tabs: { key: TabKey; label: string }[] = [
  { key: 'overview', label: 'Overview' },
  { key: 'personal', label: 'Personal Info' },
  { key: 'service', label: 'Service Details' },
  { key: 'billing', label: 'Billing' },
  { key: 'payments', label: 'Payments' },
  { key: 'tickets', label: 'Support Tickets' },
  { key: 'installation', label: 'Installation' },
  { key: 'activity', label: 'Activity' },
];

const PlaceholderCard = ({ title, module }: { title: string; module: string }) => (
  <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 py-16">
    <p className="text-sm font-medium text-slate-500">{title}</p>
    <p className="mt-1 text-xs text-slate-400">{module} module coming soon.</p>
  </div>
);

const InfoRow = ({ label, value }: { label: string; value: React.ReactNode }) => (
  <div className="flex justify-between border-b border-slate-100 py-3 last:border-0">
    <span className="text-sm text-slate-500">{label}</span>
    <span className="text-sm font-medium text-slate-800">{value ?? '—'}</span>
  </div>
);

export const CustomerProfile = ({ customer, canUpdate, canManage: _canManage }: CustomerProfileProps) => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState<TabKey>('overview');

  const renderTabContent = () => {
    switch (activeTab) {
      case 'overview':
        return (
          <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                <div className="mt-2">
                  <CustomerStatusBadge status={customer.status} />
                </div>
              </div>
              <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</p>
                <p className="mt-2 text-sm font-semibold text-slate-800">{customer.phone}</p>
              </div>
              <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">City</p>
                <p className="mt-2 text-sm font-semibold text-slate-800">{customer.city}</p>
              </div>
              <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Area</p>
                <p className="mt-2 text-sm font-semibold text-slate-800">{customer.area}</p>
              </div>
            </div>
            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h4 className="text-sm font-semibold text-slate-800">Quick Notes</h4>
              <p className="mt-2 text-sm text-slate-600">{customer.notes || 'No notes recorded.'}</p>
            </div>
            <PlaceholderCard title="Recent Activity" module="Activity Timeline" />
          </div>
        );
      case 'personal':
        return (
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Personal Information</h4>
            <div className="mt-4 divide-y divide-slate-100">
              <InfoRow label="Full Name" value={customer.full_name} />
              <InfoRow label="Father / Husband Name" value={customer.father_husband_name} />
              <InfoRow label="CNIC" value={customer.cnic} />
              <InfoRow label="Phone" value={customer.phone} />
              <InfoRow label="WhatsApp" value={customer.whatsapp} />
              <InfoRow label="Email" value={customer.email} />
              <InfoRow label="Address" value={customer.address} />
              <InfoRow label="City" value={customer.city} />
              <InfoRow label="Area" value={customer.area} />
              <InfoRow label="Emergency Contact" value={`${customer.emergency_contact_name ?? ''} ${customer.emergency_contact_phone ?? ''}`} />
            </div>
          </div>
        );
      case 'service':
        return (
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Service Details</h4>
            <div className="mt-4 divide-y divide-slate-100">
              <InfoRow label="Status" value={<CustomerStatusBadge status={customer.status} />} />
              <InfoRow label="Connection Status" value={customer.connection_status ?? 'N/A'} />
              <InfoRow label="Registration Date" value={customer.registration_date} />
              <InfoRow label="Installation Date" value={customer.installation_date} />
              <InfoRow label="Assigned Package" value={customer.assigned_package_id ?? 'None'} />
              <InfoRow label="Technician ID" value={customer.installation_technician_id} />
            </div>
          </div>
        );
      case 'billing':
        return <PlaceholderCard title="Billing Summary" module="Billing" />;
      case 'payments':
        return <PlaceholderCard title="Payment History" module="Payments" />;
      case 'tickets':
        return <PlaceholderCard title="Support Tickets" module="Support" />;
      case 'installation':
        return <PlaceholderCard title="Installation History" module="Installation" />;
      case 'activity':
        return <PlaceholderCard title="Activity Timeline" module="Audit" />;
      default:
        return null;
    }
  };

  return (
    <div className="space-y-6">
      <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div className="bg-gradient-to-br from-indigo-600 via-indigo-600 to-slate-900 px-6 py-8 text-white sm:px-8">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-100">{customer.customer_code}</p>
              <h1 className="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{customer.full_name}</h1>
              <div className="mt-3 flex flex-wrap items-center gap-3">
                <CustomerStatusBadge status={customer.status} />
                <span className="text-sm text-indigo-100">{customer.phone}</span>
                {customer.email && <span className="text-sm text-indigo-100">{customer.email}</span>}
              </div>
              <p className="mt-2 text-sm text-indigo-100">
                {customer.area}, {customer.city}
              </p>
            </div>
            <div className="flex gap-2">
              {canUpdate && (
                <Button
                  className="bg-white text-indigo-700 hover:bg-indigo-50"
                  size="sm"
                  onClick={() => navigate(`/customers/${customer.id}/edit`)}
                >
                  Edit Customer
                </Button>
              )}
            </div>
          </div>
        </div>

        <div className="border-b border-slate-200 bg-white">
          <nav className="flex overflow-x-auto px-4" aria-label="Customer tabs">
            {tabs.map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className={clsx(
                  'whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition',
                  activeTab === tab.key
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                )}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        <div className="p-6">{renderTabContent()}</div>
      </div>
    </div>
  );
};
