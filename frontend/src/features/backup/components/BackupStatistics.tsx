import React from 'react';
import { Database, Shield, FileArchive, AlertTriangle, CheckCircle } from 'lucide-react';
import type { BackupStatistics as Stats } from '../types';

export const BackupStatistics = ({ stats }: { stats: Stats | null }) => {
  if (!stats) return null;

  const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const cards = [
    { label: 'Total Backups', value: stats.total_jobs, icon: Database, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Successful', value: stats.successful_jobs, icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-50' },
    { label: 'Failed', value: stats.failed_jobs, icon: AlertTriangle, color: 'text-red-600', bg: 'bg-red-50' },
    { label: 'Files Stored', value: stats.total_files, icon: FileArchive, color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: 'Total Storage', value: formatSize(stats.total_size), icon: Shield, color: 'text-purple-600', bg: 'bg-purple-50' },
  ];

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
      {cards.map((card) => (
        <div key={card.label} className="flex items-center rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className={`mr-4 rounded-lg ${card.bg} p-2`}>
            <card.icon className={`h-6 w-6 ${card.color}`} />
          </div>
          <div>
            <p className="text-sm font-medium text-slate-500">{card.label}</p>
            <p className="text-2xl font-bold text-slate-900">{card.value}</p>
          </div>
        </div>
      ))}
    </div>
  );
};
