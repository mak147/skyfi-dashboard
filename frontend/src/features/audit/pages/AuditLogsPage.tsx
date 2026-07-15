import { useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useAuditFilterOptions, useAuditLogs } from '../api/useAudit';
import { useRequestExport } from '../api/useAudit';
import { AuditFiltersBar } from '../components/AuditFilters';
import { AuditSkeleton } from '../components/AuditSkeleton';
import { AuditTable } from '../components/AuditTable';
import { ExportDialog } from '../components/ExportDialog';
import type { AuditLogFilters } from '../types';

export const AuditLogsPage = () => {
  const { can } = usePermissions();
  const [filters, setFilters] = useState<AuditLogFilters>({ page: 1, per_page: 25 });
  const [exportOpen, setExportOpen] = useState(false);
  const list = useAuditLogs(filters);
  const options = useAuditFilterOptions();
  const requestExport = useRequestExport();

  if (list.isLoading && !list.data) {
    return <AuditSkeleton />;
  }

  if (list.error) {
    return <Alert title="Audit logs unavailable">{apiErrorMessage(list.error)}</Alert>;
  }

  const items = list.data?.data.map((row) => row.attributes) ?? [];
  const meta = list.data?.meta;

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Audit & Compliance</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Audit Logs</h1>
          <p className="mt-1 text-sm text-slate-500">
            Search, filter, and review the complete audit trail.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to="/audit">
            <Button variant="secondary">Dashboard</Button>
          </Link>
          {can('audit.export') && (
            <Button onClick={() => setExportOpen(true)}>Export</Button>
          )}
        </div>
      </header>

      <AuditFiltersBar
        filters={filters}
        options={options.data}
        onChange={setFilters}
      />

      <AuditTable items={items} isLoading={list.isLoading} />

      {meta ? (
        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
          <span className="text-slate-500">{meta.total} logs</span>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={(filters.page ?? 1) <= 1}
              onClick={() => setFilters((f) => ({ ...f, page: Math.max(1, (f.page ?? 1) - 1) }))}
            >
              Previous
            </Button>
            <span className="flex items-center px-2 text-xs text-slate-500">
              Page {meta.current_page} of {meta.last_page}
            </span>
            <Button
              size="sm"
              variant="secondary"
              disabled={(filters.page ?? 1) >= meta.last_page}
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) + 1 }))}
            >
              Next
            </Button>
          </div>
        </div>
      ) : null}

      <ExportDialog
        isOpen={exportOpen}
        onClose={() => setExportOpen(false)}
        onExport={(data) => {
          requestExport.mutate({ ...data, ...filters });
          setExportOpen(false);
        }}
        isPending={requestExport.isPending}
      />
    </div>
  );
};
