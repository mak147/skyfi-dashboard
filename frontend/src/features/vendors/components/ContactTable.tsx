import React from 'react';
import { Button } from '@/components/ui/button';
import { useDeleteVendorContact } from '../api/useVendors';
import type { VendorContact } from '../types';

interface ContactTableProps {
  contacts: VendorContact[];
  isLoading?: boolean;
  canManage?: boolean;
  onEdit?: (contact: VendorContact) => void;
}

export const ContactTable: React.FC<ContactTableProps> = ({ contacts, isLoading, canManage, onEdit }) => {
  const deleteMutation = useDeleteVendorContact();

  if (isLoading) {
    return (
      <div className="space-y-3">
        {[...Array<number>(3)].map((_, i) => (
          <div key={i} className="h-16 animate-pulse rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" />
        ))}
      </div>
    );
  }

  if (contacts.length === 0) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        No contacts found for this supplier directory.
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
            <tr>
              <th className="px-4 py-3.5">Name</th>
              <th className="px-4 py-3.5">Vendor / Department</th>
              <th className="px-4 py-3.5">Contact Details</th>
              <th className="px-4 py-3.5">Role / Badges</th>
              {canManage && <th className="px-4 py-3.5 text-right">Actions</th>}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
            {contacts.map((contact) => (
              <tr key={contact.id} className="transition hover:bg-slate-50/75 dark:hover:bg-slate-800/40">
                <td className="px-4 py-3.5 font-semibold text-slate-800 dark:text-slate-200">
                  {contact.first_name} {contact.last_name}
                  <div className="text-xs font-normal text-slate-400 dark:text-slate-500">{contact.position || 'Representative'}</div>
                </td>
                <td className="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                  <div className="font-medium text-slate-800 dark:text-slate-200">{contact.vendor_name || `Supplier #${contact.vendor_id}`}</div>
                  <div className="text-xs text-slate-400 dark:text-slate-500">{contact.department || 'General'}</div>
                </td>
                <td className="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                  {contact.email && <div className="text-xs">✉ {contact.email}</div>}
                  {contact.phone && <div className="text-xs">📞 {contact.phone}</div>}
                </td>
                <td className="px-4 py-3.5">
                  <div className="flex flex-wrap gap-1.5">
                    {Boolean(contact.is_primary) && (
                      <span className="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300">
                        Primary
                      </span>
                    )}
                    {Boolean(contact.is_emergency) && (
                      <span className="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-950/40 dark:text-red-300">
                        Emergency
                      </span>
                    )}
                  </div>
                </td>
                {canManage && (
                  <td className="px-4 py-3.5 text-right">
                    <div className="flex justify-end gap-2">
                      {onEdit && (
                        <Button variant="secondary" size="sm" onClick={() => onEdit(contact)}>
                          Edit
                        </Button>
                      )}
                      <Button
                        variant="secondary"
                        size="sm"
                        className="text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40"
                        onClick={() => {
                          if (window.confirm(`Delete contact ${contact.first_name} ${contact.last_name}?`)) {
                            deleteMutation.mutate(contact.id);
                          }
                        }}
                      >
                        Delete
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
