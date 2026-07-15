import type { PortalConnection } from '../types';

interface ConnectionCardProps {
  connection: PortalConnection;
}

export const ConnectionCard = ({ connection }: ConnectionCardProps) => {
  const conn = connection.connection;
  const pkg = connection.package;
  const router = connection.router;

  const isActive = conn.status === 'active';

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Service status</p>
            <p className="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
              {isActive ? 'Active' : (conn.status as string) ?? 'Unknown'}
            </p>
          </div>
          <span
            className={`inline-flex h-3 w-3 rounded-full ${
              isActive ? 'bg-emerald-500' : 'bg-rose-500'
            }`}
            aria-hidden="true"
          />
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-700 dark:bg-slate-900">
          <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Package</h3>
          <p className="mt-2 text-lg font-semibold text-slate-900 dark:text-white">
            {(pkg?.name as string) ?? '—'}
          </p>
          {pkg && (
            <dl className="mt-4 space-y-2 text-sm">
              <div className="flex justify-between">
                <dt className="text-slate-500">Speed profile</dt>
                <dd className="font-medium text-slate-900 dark:text-slate-200">
                  {(pkg?.speed_profile as string) ?? '—'}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-slate-500">Connection type</dt>
                <dd className="font-medium text-slate-900 dark:text-slate-200 capitalize">
                  {(conn.type as string) ?? '—'}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-slate-500">Installation date</dt>
                <dd className="font-medium text-slate-900 dark:text-slate-200">
                  {conn.installation_date
                    ? new Date(conn.installation_date as string).toLocaleDateString()
                    : '—'}
                </dd>
              </div>
            </dl>
          )}
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-700 dark:bg-slate-900">
          <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Connection details</h3>
          <dl className="mt-4 space-y-2 text-sm">
            <div className="flex justify-between">
              <dt className="text-slate-500">PPPoE username</dt>
              <dd className="font-medium text-slate-900 dark:text-slate-200">
                {(conn.pppoe_username as string) ?? '—'}
              </dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-slate-500">Connection number</dt>
              <dd className="font-medium text-slate-900 dark:text-slate-200">
                {(conn.connection_number as string) ?? '—'}
              </dd>
            </div>
            {router && (
              <>
                <div className="flex justify-between">
                  <dt className="text-slate-500">Router</dt>
                  <dd className="font-medium text-slate-900 dark:text-slate-200">
                    {(router.name as string) ?? '—'}
                  </dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-slate-500">POP site</dt>
                  <dd className="font-medium text-slate-900 dark:text-slate-200">
                    {(router.pop_site as string) ?? '—'}
                  </dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-slate-500">Tower / Sector</dt>
                  <dd className="font-medium text-slate-900 dark:text-slate-200">
                    {[router.tower, router.sector].filter(Boolean).join(' / ') || '—'}
                  </dd>
                </div>
              </>
            )}
          </dl>
        </div>
      </div>

      <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-slate-700 dark:bg-slate-900">
        <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Monthly usage</h3>
        <p className="mt-2 text-sm text-slate-500">{connection.monthly_usage.message}</p>
      </div>
    </div>
  );
};
