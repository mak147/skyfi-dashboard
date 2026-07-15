import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { getTickets } from '../api/portalApi';

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

const priorityClass = (priority: string): string => {
  const map: Record<string, string> = {
    low: 'text-slate-500',
    normal: 'text-slate-700 dark:text-slate-300',
    high: 'text-amber-600 dark:text-amber-400',
    urgent: 'text-rose-600 dark:text-rose-400',
  };
  return map[priority] ?? map.normal;
};

export const TicketList = () => {
  const [page, setPage] = useState(1);
  const ticketsQuery = useQuery({
    queryKey: ['portal', 'tickets', page],
    queryFn: () => getTickets(page),
    staleTime: 60 * 1000,
  });

  if (ticketsQuery.isLoading) {
    return <div className="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }

  if (ticketsQuery.error) {
    return (
      <Alert title="Unable to load tickets">
        {apiErrorMessage(ticketsQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  const tickets = ticketsQuery.data?.data ?? [];
  const meta = ticketsQuery.data?.meta;

  return (
    <div className="space-y-4">
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card dark:border-slate-700 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead className="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ticket</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</th>
                <th className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
              {tickets.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-sm text-slate-500">
                    No tickets found.
                  </td>
                </tr>
              ) : (
                tickets.map((ticket) => {
                  const attrs = ticket.attributes;
                  return (
                    <tr key={ticket.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                      <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                        {attrs.ticket_number as string}
                      </td>
                      <td className="max-w-xs truncate px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                        {attrs.subject as string}
                      </td>
                      <td className="whitespace-nowrap px-4 py-3 text-sm font-semibold capitalize">
                        <span className={priorityClass(attrs.priority as string)}>{attrs.priority as string}</span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span
                          className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusClass(
                            attrs.status as string,
                          )}`}
                        >
                          {attrs.status as string}
                        </span>
                      </td>
                      <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                        {new Date(attrs.created_at as string).toLocaleDateString()}
                      </td>
                      <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                        <Link
                          to={`/portal/support/${ticket.id}`}
                          className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                        >
                          View
                        </Link>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between">
          <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Previous
          </Button>
          <span className="text-sm text-slate-600 dark:text-slate-300">
            Page {meta.current_page} of {meta.last_page}
          </span>
          <Button variant="secondary" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
            Next
          </Button>
        </div>
      )}
    </div>
  );
};
