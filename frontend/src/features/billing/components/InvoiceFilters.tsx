import type { InvoiceFilters as Filters } from '../types';

export const InvoiceFilters = ({
  filters,
  onChange,
}: {
  filters: Filters;
  onChange: (filters: Filters) => void;
}) => (
  <div className="flex flex-wrap gap-3">
      <select
        className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
        value={filters.status || ''}
        onChange={(e) => onChange({ ...filters, status: e.target.value as Filters['status'] })}
      >
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending</option>
        <option value="issued">Issued</option>
        <option value="partially_paid">Partially Paid</option>
        <option value="paid">Paid</option>
        <option value="overdue">Overdue</option>
        <option value="cancelled">Cancelled</option>
        <option value="void">Void</option>
      </select>

      <input
        type="date"
        className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
        placeholder="Due from"
        value={filters.due_from || ''}
        onChange={(e) => onChange({ ...filters, due_from: e.target.value })}
      />

      <input
        type="date"
        className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
        placeholder="Due to"
        value={filters.due_to || ''}
        onChange={(e) => onChange({ ...filters, due_to: e.target.value })}
      />

      <input
        type="text"
        className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
        placeholder="Search invoices..."
        value={filters.search || ''}
        onChange={(e) => onChange({ ...filters, search: e.target.value })}
      />
    </div>
  );
