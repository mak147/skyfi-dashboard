import { useState } from 'react';

import { DiffViewer } from './DiffViewer';
import type { AuditLog } from '../types';

const severityColors: Record<string, string> = {
  info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  warning: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  critical: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
};

interface AuditTableProps {
  items: AuditLog[];
  isLoading?: boolean;
}

export const AuditTable = ({ items, isLoading }: AuditTableProps) => {
  const [expandedId, setExpandedId] = useState<number | null>(null);

  if (isLoading) {
    return (
      <div className="space-y-2">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="h-16 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
        ))}
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">
        No audit logs found matching your filters.
      </div>
    );
  }

  return (
    <div className="space-y-2">
      {items.map((item) => {
        const isExpanded = expandedId === item.id;
        const severityClass = severityColors[item.severity] ?? severityColors.info;

        return (
          <div key={item.id} className="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            <button
              type="button"
              className="flex w-full items-center gap-4 px-4 py-3 text-left text-sm"
              onClick={() => setExpandedId(isExpanded ? null : item.id)}
            >
              <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${severityClass}`}>
                {item.severity}
              </span>
              <span className="font-mono text-xs text-slate-500 dark:text-slate-400">{item.action}</span>
              <span className="text-slate-700 dark:text-slate-200">{item.entity_type}{item.entity_id ? ` #${item.entity_id}` : ''}</span>
              {item.module && <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-400">{item.module}</span>}
              <span className="ml-auto text-xs text-slate-400">{item.user_name ?? 'System'} · {new Date(item.created_at).toLocaleString()}</span>
              <span className="text-slate-400">{isExpanded ? '▲' : '▼'}</span>
            </button>

            {isExpanded && (
              <div className="border-t border-slate-100 px-4 py-4 dark:border-slate-800">
                <div className="mb-4 grid grid-cols-2 gap-4 text-sm lg:grid-cols-4">
                  <div>
                    <p className="text-xs font-semibold uppercase text-slate-400">User</p>
                    <p className="text-slate-700 dark:text-slate-200">{item.user_name ?? 'System'}{item.user_email ? ` (${item.user_email})` : ''}</p>
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase text-slate-400">IP Address</p>
                    <p className="font-mono text-slate-700 dark:text-slate-200">{item.ip_address ?? '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase text-slate-400">Correlation ID</p>
                    <p className="font-mono text-xs text-slate-700 dark:text-slate-200">{item.correlation_id ?? '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs font-semibold uppercase text-slate-400">URL</p>
                    <p className="truncate font-mono text-xs text-slate-700 dark:text-slate-200">{item.url ?? '—'}</p>
                  </div>
                </div>

                {(item.old_values || item.new_values) && (
                  <DiffViewer oldValues={item.old_values} newValues={item.new_values} />
                )}

                {item.compliance_tags && item.compliance_tags.length > 0 && (
                  <div className="mt-3 flex flex-wrap gap-1">
                    {item.compliance_tags.map((tag) => (
                      <span key={tag} className="rounded-md bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {tag}
                      </span>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
};
