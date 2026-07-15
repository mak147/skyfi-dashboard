import type { ApprovalRecord } from '../types';
import { clsx } from 'clsx';

export const ApprovalTimeline = ({ approvals }: { approvals: ApprovalRecord[] }) => {
  if (!approvals?.length) {
    return <p className="py-6 text-center text-sm text-slate-400">No approval history yet.</p>;
  }
  return (
    <div className="space-y-4">
      {approvals.map((a) => (
        <div key={a.id} className="flex gap-3">
          <div className={clsx('mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm', a.decision === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700')}>
            {a.decision === 'approved' ? '✓' : '✕'}
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2">
              <span className="font-semibold text-slate-800">{a.approver_name}</span>
              <span className={clsx('text-xs font-semibold capitalize', a.decision === 'approved' ? 'text-emerald-600' : 'text-red-600')}>{a.decision}</span>
            </div>
            <time className="text-xs text-slate-400">{new Date(a.decided_at).toLocaleString()}</time>
            {a.comments ? <p className="mt-1 text-sm text-slate-600">{a.comments}</p> : null}
          </div>
        </div>
      ))}
    </div>
  );
};
