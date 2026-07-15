import React from 'react';
import { Clock, CheckCircle2, XCircle, AlertCircle } from 'lucide-react';
import type { BackupJob } from '../types';

export const BackupTable = ({ jobs }: { jobs: BackupJob[] }) => {
  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed': return <CheckCircle2 className="h-4 w-4 text-green-500" />;
      case 'failed': return <XCircle className="h-4 w-4 text-red-500" />;
      case 'running': return <Clock className="h-4 w-4 text-blue-500 animate-spin" />;
      default: return <AlertCircle className="h-4 w-4 text-slate-400" />;
    }
  };

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table className="min-w-full divide-y divide-slate-200 text-left">
        <thead className="bg-slate-50">
          <tr>
            <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Job ID</th>
            <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
            <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Source</th>
            <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Started</th>
            <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Duration</th>
            <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-200">
          {jobs.map((job) => (
            <tr key={job.id} className="hover:bg-slate-50 transition-colors">
              <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">#{job.id}</td>
              <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600 capitalize">{job.type}</td>
              <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                {job.schedule_name || 'Manual Trigger'}
              </td>
              <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{job.started_at || '—'}</td>
              <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                {job.finished_at && job.started_at 
                  ? `${Math.round((new Date(job.finished_at).getTime() - new Date(job.started_at).getTime()) / 1000)}s` 
                  : '—'}
              </td>
              <td className="whitespace-nowrap px-6 py-4">
                <div className="flex items-center space-x-2">
                  {getStatusIcon(job.status)}
                  <span className={`text-sm font-medium capitalize ${
                    job.status === 'completed' ? 'text-green-700' : 
                    job.status === 'failed' ? 'text-red-700' : 
                    job.status === 'running' ? 'text-blue-700' : 'text-slate-700'
                  }`}>
                    {job.status}
                  </span>
                </div>
              </td>
            </tr>
          ))}
          {!jobs.length && (
            <tr>
              <td colSpan={6} className="px-6 py-10 text-center text-sm text-slate-500">
                No backup jobs found.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
};
