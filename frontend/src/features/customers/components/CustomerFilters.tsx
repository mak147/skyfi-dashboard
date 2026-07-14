import { Button } from '@/components/ui/button';

import type { CustomerFilters as Filters, CustomerStatus } from '../types';
import { CustomerSearch } from './CustomerSearch';

interface CustomerFiltersProps {
  filters: Filters;
  onChange: (filters: Filters) => void;
}

const statusOptions: { value: CustomerStatus | ''; label: string }[] = [
  { value: '', label: 'All Statuses' },
  { value: 'lead', label: 'Lead' },
  { value: 'prospect', label: 'Prospect' },
  { value: 'active', label: 'Active' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'disconnected', label: 'Disconnected' },
  { value: 'archived', label: 'Archived' },
];

export const CustomerFiltersBar = ({ filters, onChange }: CustomerFiltersProps) => {
  const hasFilters = filters.status || filters.city || filters.area || filters.search;

  const clearFilters = () => {
    onChange({});
  };

  return (
    <div className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="flex-1">
          <CustomerSearch
            value={filters.search ?? ''}
            onChange={(search) => onChange({ ...filters, search: search || undefined })}
          />
        </div>
        <div className="flex gap-2">
          <select
            className="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            value={filters.status ?? ''}
            onChange={(e) => onChange({ ...filters, status: (e.target.value as CustomerStatus) || undefined })}
          >
            {statusOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          <input
            type="text"
            placeholder="City"
            className="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            value={filters.city ?? ''}
            onChange={(e) => onChange({ ...filters, city: e.target.value || undefined })}
          />
          <input
            type="text"
            placeholder="Area"
            className="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            value={filters.area ?? ''}
            onChange={(e) => onChange({ ...filters, area: e.target.value || undefined })}
          />
        </div>
      </div>
      {hasFilters && (
        <div className="flex items-center justify-between border-t border-slate-100 pt-3">
          <p className="text-xs text-slate-500">Filters are applied automatically.</p>
          <Button variant="ghost" size="sm" onClick={clearFilters}>
            Clear filters
          </Button>
        </div>
      )}
    </div>
  );
};
