import type { Invoice } from '../types';
import { InvoiceStatusBadge } from './InvoiceStatusBadge';

export const BillingSummary = ({ invoice }: { invoice: Invoice }) => (
  <div className="space-y-6">
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Invoice Summary</h3>
        <div className="mt-4 space-y-4">
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Status</span>
            <InvoiceStatusBadge status={invoice.status} />
          </div>
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Invoice Number</span>
            <span className="text-sm font-medium text-slate-900">{invoice.invoice_number}</span>
          </div>
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Issue Date</span>
            <span className="text-sm font-medium text-slate-900">{invoice.issue_date}</span>
          </div>
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Due Date</span>
            <span className="text-sm font-medium text-slate-900">{invoice.due_date}</span>
          </div>
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Billing Period</span>
            <span className="text-sm font-medium text-slate-900">
              {invoice.billing_period_start} — {invoice.billing_period_end}
            </span>
          </div>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Amount Breakdown</h3>
        <div className="mt-4 space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Subtotal</span>
            <span className="text-sm font-medium text-slate-900 tabular-nums">
              {invoice.currency} {Number(invoice.subtotal).toLocaleString()}
            </span>
          </div>
          {invoice.tax_amount > 0 && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-slate-500">Tax</span>
              <span className="text-sm font-medium text-slate-900 tabular-nums">
                {invoice.currency} {Number(invoice.tax_amount).toLocaleString()}
              </span>
            </div>
          )}
          {invoice.discount_amount > 0 && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-slate-500">Discount</span>
              <span className="text-sm font-medium text-emerald-600 tabular-nums">
                -{invoice.currency} {Number(invoice.discount_amount).toLocaleString()}
              </span>
            </div>
          )}
          {invoice.late_fee_amount > 0 && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-slate-500">Late Fee</span>
              <span className="text-sm font-medium text-red-600 tabular-nums">
                +{invoice.currency} {Number(invoice.late_fee_amount).toLocaleString()}
              </span>
            </div>
          )}
          {invoice.previous_balance > 0 && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-slate-500">Previous Balance</span>
              <span className="text-sm font-medium text-slate-900 tabular-nums">
                {invoice.currency} {Number(invoice.previous_balance).toLocaleString()}
              </span>
            </div>
          )}
          <div className="border-t border-slate-100 pt-3">
            <div className="flex items-center justify-between">
              <span className="text-sm font-semibold text-slate-900">Total</span>
              <span className="text-lg font-bold text-slate-900 tabular-nums">
                {invoice.currency} {Number(invoice.total_amount).toLocaleString()}
              </span>
            </div>
          </div>
          <div className="flex items-center justify-between">
            <span className="text-sm text-slate-500">Balance Due</span>
            <span className="text-sm font-bold text-indigo-600 tabular-nums">
              {invoice.currency} {Number(invoice.balance_due).toLocaleString()}
            </span>
          </div>
        </div>
      </div>
    </div>
);
