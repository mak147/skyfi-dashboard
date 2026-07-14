import { useNavigate } from 'react-router-dom';
import type { Invoice } from '../types';
import { InvoiceStatusBadge } from './InvoiceStatusBadge';

export const InvoiceTable = ({
  invoices,
  selected,
  onSelect,
  sort,
  onSort,
}: {
  invoices: Invoice[];
  selected: number[];
  onSelect: (ids: number[]) => void;
  sort: string;
  onSort: (s: string) => void;
}) => {
  const navigate = useNavigate();

  const header = (label: string, field: string) => (
    <th
      className="cursor-pointer px-4 py-3 text-left text-xs uppercase text-slate-500"
      onClick={() => onSort(sort === field ? `-${field}` : field)}
    >
      {label}
      {sort.replace('-', '') === field ? ' ↕' : ''}
    </th>
  );

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table className="min-w-full">
        <thead className="bg-slate-50">
          <tr>
            <th className="px-4">
              <input
                type="checkbox"
                checked={invoices.length > 0 && selected.length === invoices.length}
                onChange={(e) => onSelect(e.target.checked ? invoices.map((i) => i.id) : [])}
              />
            </th>
            {header('Number', 'invoice_number')}
            {header('Customer', 'customer_name')}
            {header('Issue Date', 'issue_date')}
            {header('Due Date', 'due_date')}
            {header('Total', 'total_amount')}
            {header('Status', 'status')}
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {invoices.map((invoice) => (
            <tr
              key={invoice.id}
              className="cursor-pointer hover:bg-slate-50"
              onClick={() => navigate(`/billing/${invoice.id}`)}
            >
              <td className="px-4 py-3">
                <input
                  type="checkbox"
                  checked={selected.includes(invoice.id)}
                  onChange={(e) =>
                    onSelect(
                      e.target.checked
                        ? [...selected, invoice.id]
                        : selected.filter((x) => x !== invoice.id),
                    )
                  }
                  onClick={(e) => e.stopPropagation()}
                />
              </td>
              <td className="px-4 py-3 text-sm font-semibold text-indigo-600">{invoice.invoice_number}</td>
              <td className="px-4 py-3">
                <p className="text-sm font-semibold text-slate-900">{invoice.customer_name || '—'}</p>
                <p className="text-xs text-slate-400">{invoice.customer_code || ''}</p>
              </td>
              <td className="px-4 py-3 text-sm text-slate-600">{invoice.issue_date}</td>
              <td className="px-4 py-3 text-sm text-slate-600">{invoice.due_date}</td>
              <td className="px-4 py-3 text-sm font-semibold tabular-nums">
                {invoice.currency} {Number(invoice.total_amount).toLocaleString()}
              </td>
              <td className="px-4 py-3">
                <InvoiceStatusBadge status={invoice.status} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      {!invoices.length && <p className="py-16 text-center text-sm text-slate-500">No invoices match the current filters.</p>}
    </div>
  );
};
