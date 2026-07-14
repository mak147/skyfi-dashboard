import { useNavigate } from 'react-router-dom';
import { clsx } from 'clsx';
import type { Invoice } from '../types';
import { InvoiceStatusBadge } from './InvoiceStatusBadge';

export const InvoiceCard = ({
  invoice,
  selected,
  onSelect,
}: {
  invoice: Invoice;
  selected: boolean;
  onSelect: (checked: boolean) => void;
}) => {
  const navigate = useNavigate();

  return (
    <div
      className={clsx(
        'rounded-xl border bg-white p-4 shadow-sm',
        selected ? 'border-indigo-300 ring-1 ring-indigo-200' : 'border-slate-200',
      )}
      onClick={() => navigate(`/billing/${invoice.id}`)}
    >
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-semibold text-indigo-600">{invoice.invoice_number}</p>
          <p className="mt-1 text-sm font-medium text-slate-900">{invoice.customer_name || '—'}</p>
          <p className="text-xs text-slate-400">{invoice.customer_code || ''}</p>
        </div>
        <input
          type="checkbox"
          checked={selected}
          onChange={(e) => onSelect(e.target.checked)}
          onClick={(e) => e.stopPropagation()}
        />
      </div>
      <div className="mt-3 flex items-center justify-between">
        <div className="text-xs text-slate-500">
          <p>Due: {invoice.due_date}</p>
          <p>Issue: {invoice.issue_date}</p>
        </div>
        <div className="text-right">
          <p className="text-sm font-semibold tabular-nums">
            {invoice.currency} {Number(invoice.total_amount).toLocaleString()}
          </p>
          <InvoiceStatusBadge status={invoice.status} />
        </div>
      </div>
    </div>
  );
};
