import { useState } from 'react';

import { Button } from '@/components/ui/button';

import type { AuditExportFormat, ExportRequest } from '../types';

interface ExportDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onExport: (data: ExportRequest) => void;
  isPending?: boolean;
}

export const ExportDialog = ({ isOpen, onClose, onExport, isPending }: ExportDialogProps) => {
  const [format, setFormat] = useState<AuditExportFormat>('csv');

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40" onClick={onClose}>
      <div
        className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 className="text-lg font-bold text-slate-900 dark:text-white">Export Audit Logs</h2>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Download audit log data in your preferred format.
        </p>

        <div className="mt-5 space-y-4">
          <div>
            <label className="block text-sm font-semibold text-slate-700 dark:text-slate-200">Format</label>
            <div className="mt-2 flex gap-3">
              <button
                type="button"
                onClick={() => setFormat('csv')}
                className={`flex-1 rounded-lg border px-4 py-3 text-sm font-semibold transition ${
                  format === 'csv'
                    ? 'border-indigo-400 bg-indigo-50 text-indigo-700 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'
                    : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                }`}
              >
                📄 CSV
                <p className="mt-1 text-xs font-normal text-slate-500 dark:text-slate-400">Spreadsheet compatible</p>
              </button>
              <button
                type="button"
                onClick={() => setFormat('json')}
                className={`flex-1 rounded-lg border px-4 py-3 text-sm font-semibold transition ${
                  format === 'json'
                    ? 'border-indigo-400 bg-indigo-50 text-indigo-700 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'
                    : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                }`}
              >
                📦 JSON
                <p className="mt-1 text-xs font-normal text-slate-500 dark:text-slate-400">Structured data format</p>
              </button>
            </div>
          </div>
        </div>

        <div className="mt-6 flex justify-end gap-3">
          <Button variant="secondary" onClick={onClose} disabled={isPending}>
            Cancel
          </Button>
          <Button
            onClick={() => onExport({ format })}
            disabled={isPending}
          >
            {isPending ? 'Exporting…' : 'Export'}
          </Button>
        </div>
      </div>
    </div>
  );
};
