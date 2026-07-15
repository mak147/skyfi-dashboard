import React, { useEffect, useState } from 'react';
import { History, RotateCcw, ShieldCheck } from 'lucide-react';
import { backupApi } from '../api/backupApi';
import type { BackupFile, RestoreHistory } from '../types';

export const RestoreCenterPage = () => {
  const [files, setFiles] = useState<BackupFile[]>([]);
  const [history, setHistory] = useState<RestoreHistory[]>([]);
  const [, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      backupApi.getFiles({ perPage: 20 }),
      backupApi.getRestoreHistory()
    ]).then(([filesRes, historyRes]) => {
      setFiles(filesRes.data.items);
      setHistory(historyRes.data);
      setLoading(false);
    });
  }, []);

  const handleRestore = async (fileId: number) => {
    if (!confirm('Are you sure you want to initiate a restoration? This will overwrite current data.')) return;
    try {
      await backupApi.executeRestore({ backup_file_id: fileId, target_environment: 'production' });
      alert('Restoration process started.');
    } catch {
      alert('Failed to start restoration.');
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Restore Center</h1>
        <p className="text-slate-500">Restore application data from verified backup points.</p>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="space-y-4">
          <h2 className="text-lg font-bold text-slate-900 flex items-center">
            <ShieldCheck className="mr-2 h-5 w-5 text-green-600" />
            Available Backups
          </h2>
          <div className="space-y-3">
            {files.map((file) => (
              <div key={file.id} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-bold text-slate-900">{file.file_path.split('/').pop()}</p>
                    <p className="text-xs text-slate-500">
                      Created: {new Date(file.created_at).toLocaleString()} | Size: {(file.file_size / 1024).toFixed(2)} KB
                    </p>
                  </div>
                  <button 
                    onClick={() => handleRestore(file.id)}
                    className="flex items-center space-x-2 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-100 transition-colors"
                  >
                    <RotateCcw className="h-4 w-4" />
                    <span>Restore</span>
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-4">
          <h2 className="text-lg font-bold text-slate-900 flex items-center">
            <History className="mr-2 h-5 w-5 text-slate-600" />
            Restore History
          </h2>
          <div className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
             <table className="min-w-full divide-y divide-slate-200 text-left">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-4 py-2 text-xs font-semibold text-slate-500">Date</th>
                  <th className="px-4 py-2 text-xs font-semibold text-slate-500">Environment</th>
                  <th className="px-4 py-2 text-xs font-semibold text-slate-500">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200">
                {history.map((h) => (
                  <tr key={h.id}>
                    <td className="px-4 py-3 text-sm text-slate-600">{new Date(h.created_at).toLocaleDateString()}</td>
                    <td className="px-4 py-3 text-sm text-slate-600 uppercase font-bold text-[10px]">{h.target_environment}</td>
                    <td className="px-4 py-3">
                      <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${
                        h.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                      }`}>
                        {h.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
             </table>
          </div>
        </div>
      </div>
    </div>
  );
};
