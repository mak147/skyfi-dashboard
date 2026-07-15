import { useState } from 'react';
import { Button } from '@/components/ui/button';

import { useTestConnector, useUpdateConnector } from '../api/useIntegration';
import type { ConnectorItem } from '../types';

interface Props {
  items: ConnectorItem[];
}

const categoryIcon: Record<string, string> = {
  payment: '💳',
  messaging: '✉',
  mapping: '🗺',
};

export const ConnectorCards = ({ items }: Props) => {
  const [expanded, setExpanded] = useState<string | null>(null);
  const updateConnector = useUpdateConnector();
  const testConnector = useTestConnector();
  const [testResult, setTestResult] = useState<Record<string, { success: boolean; message: string }>>({});

  const handleTest = (type: string) => {
    testConnector.mutate(type, {
      onSuccess: (res) => setTestResult((prev) => ({ ...prev, [type]: res as unknown as { success: boolean; message: string } })),
    });
  };

  const handleToggle = (item: ConnectorItem) => {
    updateConnector.mutate({ type: item.connector_type, data: { is_enabled: !item.is_enabled } as Partial<ConnectorItem> });
  };

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {items.map((c) => {
        const meta = c._meta;
        const cat = meta?.category ?? 'other';
        const isExpanded = expanded === c.connector_type;

        return (
          <div key={c.id} className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-3">
                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-lg dark:bg-indigo-950">
                  {categoryIcon[cat] ?? '🔌'}
                </span>
                <div>
                  <p className="font-semibold text-slate-900 dark:text-white">{c.name}</p>
                  <p className="text-xs text-slate-500">{c.connector_type} · {cat}</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => handleToggle(c)}
                className={`relative h-6 w-11 rounded-full transition-colors ${c.is_enabled ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600'}`}
                title={c.is_enabled ? 'Disable' : 'Enable'}
              >
                <span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform ${c.is_enabled ? 'left-[22px]' : 'left-0.5'}`} />
              </button>
            </div>

            {c.description && <p className="mt-2 text-sm text-slate-500">{c.description}</p>}

            {isExpanded && (
              <div className="mt-3 space-y-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                <p className="text-xs font-semibold text-slate-600 dark:text-slate-400">Configuration</p>
                <pre className="overflow-x-auto rounded-lg bg-slate-50 p-2 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  {JSON.stringify(c.config, null, 2)}
                </pre>
                {testResult[c.connector_type] && (
                  <p className={`text-xs ${testResult[c.connector_type].success ? 'text-green-600' : 'text-red-600'}`}>
                    {testResult[c.connector_type].message}
                  </p>
                )}
              </div>
            )}

            <div className="mt-3 flex gap-2">
              <Button size="sm" variant="secondary" onClick={() => setExpanded(isExpanded ? null : c.connector_type)}>
                {isExpanded ? 'Hide' : 'Configure'}
              </Button>
              <Button size="sm" variant="secondary" disabled={testConnector.isPending} onClick={() => handleTest(c.connector_type)}>
                Test
              </Button>
            </div>
          </div>
        );
      })}
    </div>
  );
};
