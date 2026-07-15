import { useQuery } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { getDashboard } from '../api/portalApi';

import { PortalSkeleton } from './PortalSkeleton';

const formatCurrency = (amount: number, currency = 'PKR'): string =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);

const StatusBadge = ({ status, isOnline }: { status: string; isOnline: boolean }) => (
  <span
    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
      isOnline
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
    }`}
  >
    {isOnline ? '● Online' : '● Offline'} · {status}
  </span>
);

export const CustomerDashboard = () => {
  const dashboardQuery = useQuery({
    queryKey: ['portal', 'dashboard'],
    queryFn: getDashboard,
    staleTime: 2 * 60 * 1000,
  });

  if (dashboardQuery.isLoading) {
    return <PortalSkeleton />;
  }

  if (dashboardQuery.error) {
    return (
      <Alert title="Dashboard unavailable">
        {apiErrorMessage(dashboardQuery.error, 'Unable to load your dashboard. Please try again.')}
      </Alert>
    );
  }

  const dashboard = dashboardQuery.data;
  if (!dashboard) {
    return <Alert title="Dashboard unavailable">No dashboard data was returned.</Alert>;
  }

  const customer = dashboard.customer;
  const connection = dashboard.connection;
  const packageInfo = dashboard.package;

  return (
    <div className="space-y-6">
      <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card dark:border-slate-700 dark:bg-slate-900">
        <div className="bg-gradient-to-br from-indigo-600 via-indigo-600 to-slate-900 px-6 py-8 text-white sm:px-8">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-100">Welcome back</p>
          <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{customer.full_name}</h1>
          <p className="mt-2 text-sm text-indigo-100">
            Customer ID: {customer.customer_code} · {customer.city}, {customer.area}
          </p>
          <div className="mt-4">
            <StatusBadge status={(connection?.status as string) ?? 'unknown'} isOnline={dashboard.is_online} />
          </div>
        </div>
      </section>

      <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <DashboardCard
          label="Outstanding Balance"
          value={formatCurrency(dashboard.outstanding_balance)}
          hint="Current amount due"
          tone="danger"
        />
        <DashboardCard
          label="Current Package"
          value={(packageInfo?.name as string) ?? '—'}
          hint={(packageInfo?.speed_profile as string) ?? 'Package details'}
          tone="neutral"
        />
        <DashboardCard
          label="Active Tickets"
          value={String(dashboard.active_tickets.length)}
          hint="Open support requests"
          tone="warning"
        />
        <DashboardCard
          label="Latest Invoice"
          value={dashboard.latest_invoice?.invoice_number ?? '—'}
          hint={
            dashboard.latest_invoice
              ? `Due ${new Date(dashboard.latest_invoice.due_date).toLocaleDateString()}`
              : 'No invoices yet'
          }
          tone="info"
        />
      </section>

      <section className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-700 dark:bg-slate-900">
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Recent Payments</h2>
          {dashboard.recent_payments.length === 0 ? (
            <p className="mt-4 text-sm text-slate-500">No recent payments.</p>
          ) : (
            <ul className="mt-4 space-y-3">
              {dashboard.recent_payments.map((payment) => (
                <li
                  key={payment.id}
                  className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50"
                >
                  <div>
                    <p className="text-sm font-semibold text-slate-900 dark:text-white">{payment.payment_number}</p>
                    <p className="text-xs text-slate-500">{new Date(payment.payment_date).toLocaleDateString()}</p>
                  </div>
                  <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                    {formatCurrency(Number(payment.amount))}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-700 dark:bg-slate-900">
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Notifications</h2>
          {dashboard.recent_notifications.length === 0 ? (
            <p className="mt-4 text-sm text-slate-500">No recent notifications.</p>
          ) : (
            <ul className="mt-4 space-y-3">
              {dashboard.recent_notifications.map((notification) => (
                <li
                  key={notification.id}
                  className="rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50"
                >
                  <p className="text-sm font-semibold text-slate-900 dark:text-white">{notification.title}</p>
                  <p className="text-xs text-slate-500 line-clamp-2">{notification.body}</p>
                </li>
              ))}
            </ul>
          )}
        </div>
      </section>
    </div>
  );
};

const DashboardCard = ({
  label,
  value,
  hint,
  tone,
}: {
  label: string;
  value: string;
  hint: string;
  tone: 'danger' | 'warning' | 'info' | 'neutral';
}) => {
  const toneClasses = {
    danger: 'text-rose-600 dark:text-rose-400',
    warning: 'text-amber-600 dark:text-amber-400',
    info: 'text-indigo-600 dark:text-indigo-400',
    neutral: 'text-slate-900 dark:text-white',
  };

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-700 dark:bg-slate-900">
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
      <p className={`mt-2 text-2xl font-bold tracking-tight ${toneClasses[tone]}`}>{value}</p>
      <p className="mt-1 text-xs text-slate-500">{hint}</p>
    </div>
  );
};
