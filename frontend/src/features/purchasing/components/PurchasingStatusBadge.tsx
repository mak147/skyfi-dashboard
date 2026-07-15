import { clsx } from 'clsx';

type StatusType = 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'cancelled' | 'converted' | 'sent' | 'partially_received' | 'fully_received' | 'closed' | 'received' | 'partial' | 'returned' | 'registered' | 'verified' | 'disputed' | 'paid';

const STATUS_MAP: Record<StatusType, { label: string; tone: string }> = {
  draft: { label: 'Draft', tone: 'bg-slate-100 text-slate-700 ring-slate-200' },
  pending_approval: { label: 'Pending Approval', tone: 'bg-amber-50 text-amber-700 ring-amber-200' },
  approved: { label: 'Approved', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  rejected: { label: 'Rejected', tone: 'bg-red-50 text-red-700 ring-red-200' },
  cancelled: { label: 'Cancelled', tone: 'bg-slate-100 text-slate-500 ring-slate-200' },
  converted: { label: 'Converted', tone: 'bg-indigo-50 text-indigo-700 ring-indigo-200' },
  sent: { label: 'Sent', tone: 'bg-blue-50 text-blue-700 ring-blue-200' },
  partially_received: { label: 'Partially Received', tone: 'bg-sky-50 text-sky-700 ring-sky-200' },
  fully_received: { label: 'Fully Received', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  closed: { label: 'Closed', tone: 'bg-slate-100 text-slate-600 ring-slate-200' },
  received: { label: 'Received', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  partial: { label: 'Partial', tone: 'bg-sky-50 text-sky-700 ring-sky-200' },
  returned: { label: 'Returned', tone: 'bg-orange-50 text-orange-700 ring-orange-200' },
  registered: { label: 'Registered', tone: 'bg-blue-50 text-blue-700 ring-blue-200' },
  verified: { label: 'Verified', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  disputed: { label: 'Disputed', tone: 'bg-red-50 text-red-700 ring-red-200' },
  paid: { label: 'Paid', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
};

export const PurchasingStatusBadge = ({ status }: { status: string }) => {
  const config = STATUS_MAP[status as StatusType] ?? { label: status.replaceAll('_', ' '), tone: 'bg-slate-100 text-slate-600 ring-slate-200' };
  return <span className={clsx('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', config.tone)}>{config.label}</span>;
};

const PRIORITY_MAP: Record<string, { label: string; tone: string }> = {
  low: { label: 'Low', tone: 'bg-slate-100 text-slate-600' },
  normal: { label: 'Normal', tone: 'bg-blue-50 text-blue-700' },
  high: { label: 'High', tone: 'bg-amber-50 text-amber-700' },
  urgent: { label: 'Urgent', tone: 'bg-red-50 text-red-700' },
};

export const PriorityBadge = ({ priority }: { priority: string }) => {
  const config = PRIORITY_MAP[priority] ?? { label: priority, tone: 'bg-slate-100 text-slate-600' };
  return <span className={clsx('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', config.tone)}>{config.label}</span>;
};
