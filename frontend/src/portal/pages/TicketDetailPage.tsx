import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';
import type { SupportTicket, TicketComment, TimelineItem } from '@/features/support/types';

import { getTicket, requestTicketClosure } from '../api/portalApi';
import { CardSkeleton } from '../components/PortalSkeleton';
import { TicketReplyForm } from '../components/TicketReplyForm';

const statusClass = (status: string): string => {
  const map: Record<string, string> = {
    new: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    open: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    assigned: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    waiting_customer: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    escalated: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    resolved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    closed: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    cancelled: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
  };
  return map[status] ?? map.new;
};

interface TicketDetailData {
  ticket: SupportTicket;
  comments: TicketComment[];
  timeline: TimelineItem[];
}

export const TicketDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const ticketId = Number(id);
  const queryClient = useQueryClient();

  const ticketQuery = useQuery({
    queryKey: ['portal', 'ticket', ticketId],
    queryFn: () => getTicket(ticketId),
    enabled: !Number.isNaN(ticketId),
    staleTime: 30 * 1000,
  });

  const closeMutation = useMutation({
    mutationFn: () => requestTicketClosure(ticketId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['portal', 'ticket', ticketId] }),
  });

  if (ticketQuery.isLoading) {
    return <CardSkeleton rows={8} />;
  }

  if (ticketQuery.error) {
    return (
      <Alert title="Unable to load ticket">
        {apiErrorMessage(ticketQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  const detail = (ticketQuery.data ?? {}) as Partial<TicketDetailData>;
  const ticket = detail.ticket;

  if (!ticket) {
    return <Alert title="Ticket not found">The requested ticket could not be found.</Alert>;
  }

  const comments = detail.comments ?? [];
  const timeline = detail.timeline ?? [];
  const canClose = !['closed', 'cancelled', 'resolved'].includes(ticket.status);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            {ticket.ticket_number}
          </h1>
          <p className="mt-1 text-sm text-slate-500">{ticket.subject}</p>
        </div>
        <div className="flex items-center gap-3">
          <span
            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusClass(ticket.status)}`}
          >
            {ticket.status}
          </span>
          {canClose && (
            <Button
              variant="secondary"
              size="sm"
              isLoading={closeMutation.isPending}
              onClick={() => closeMutation.mutate()}
            >
              Request closure
            </Button>
          )}
        </div>
      </div>

      {closeMutation.error ? (
        <Alert title="Unable to request closure">
          {apiErrorMessage(closeMutation.error, 'Please try again later.')}
        </Alert>
      ) : null}

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Details</h2>
        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</p>
            <p className="mt-1 text-sm text-slate-900 dark:text-white">{ticket.category_name ?? '—'}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</p>
            <p className="mt-1 text-sm capitalize text-slate-900 dark:text-white">{ticket.priority}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Created</p>
            <p className="mt-1 text-sm text-slate-900 dark:text-white">
              {new Date(ticket.created_at).toLocaleString()}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned to</p>
            <p className="mt-1 text-sm text-slate-900 dark:text-white">{ticket.assigned_staff_name ?? 'Unassigned'}</p>
          </div>
        </div>
        <div className="mt-6">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</p>
          <p className="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">
            {ticket.description}
          </p>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Reply</h2>
        <div className="mt-4">
          <TicketReplyForm ticketId={ticketId} />
        </div>
      </div>

      <div className="space-y-4">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Activity</h2>
        {[...timeline, ...comments]
          .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
          .map((item) => {
            const isCommentItem = (value: TicketComment | TimelineItem): value is TicketComment =>
              'type' in value;
            const isComment = isCommentItem(item);
            const author = isComment
              ? item.author_user_name ?? item.author_customer_name ?? 'You'
              : item.author_user_id
                ? 'Staff'
                : 'System';

            return (
              <div
                key={`${isComment ? 'c' : 't'}-${item.id}`}
                className="rounded-xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-700 dark:bg-slate-900"
              >
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                  <p className="text-sm font-semibold text-slate-900 dark:text-white">
                    {isComment ? 'Reply' : item.type}
                  </p>
                  <p className="text-xs text-slate-500">{new Date(item.created_at).toLocaleString()}</p>
                </div>
                <p className="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-400">{author}</p>
                <p className="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">
                  {item.body}
                </p>
              </div>
            );
          })}
        {comments.length === 0 && timeline.length === 0 ? (
          <p className="text-sm text-slate-500">No activity yet.</p>
        ) : null}
      </div>

      <div className="flex justify-start">
        <Button variant="secondary" size="sm" asChild>
          <Link to="/portal/support">Back to tickets</Link>
        </Button>
      </div>
    </div>
  );
};
