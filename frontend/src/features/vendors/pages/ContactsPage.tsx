import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ContactTable } from '../components/ContactTable';
import { ContactForm } from '../components/ContactForm';
import { useVendorContacts } from '../api/useVendors';
import { usePermissions } from '@/hooks/usePermissions';
import type { VendorContact } from '../types';

export const ContactsPage = () => {
  const [search, setSearch] = useState('');
  const { data: contacts = [], isLoading } = useVendorContacts();
  const { can } = usePermissions();
  const canManage = can('vendors.create') || can('vendors.update');

  const [isModalOpen, setModalOpen] = useState(false);
  const [editingContact, setEditingContact] = useState<VendorContact | null>(null);

  const filteredContacts = contacts.filter((c) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (
      c.first_name.toLowerCase().includes(q) ||
      c.last_name.toLowerCase().includes(q) ||
      (c.email && c.email.toLowerCase().includes(q)) ||
      (c.phone && c.phone.toLowerCase().includes(q)) ||
      (c.department && c.department.toLowerCase().includes(q)) ||
      (c.vendor_name && c.vendor_name.toLowerCase().includes(q))
    );
  });

  return (
    <div className="space-y-6 p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-800 dark:text-slate-100">All Supplier Contacts Directory</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Central repository of representatives, primary contacts, accounts departments, and emergency support numbers.
          </p>
        </div>

        {canManage && (
          <Button
            size="sm"
            onClick={() => {
              setEditingContact(null);
              setModalOpen(true);
            }}
          >
            + Add Contact
          </Button>
        )}
      </div>

      <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div className="flex-1 min-w-[240px]">
          <input
            type="text"
            placeholder="Search contacts across suppliers by name, department, email or phone..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          />
        </div>
      </div>

      <ContactTable
        contacts={filteredContacts}
        isLoading={isLoading}
        canManage={canManage}
        onEdit={(c) => {
          setEditingContact(c);
          setModalOpen(true);
        }}
      />

      <ContactForm
        initialData={editingContact}
        isOpen={isModalOpen}
        onClose={() => {
          setModalOpen(false);
          setEditingContact(null);
        }}
      />
    </div>
  );
};
