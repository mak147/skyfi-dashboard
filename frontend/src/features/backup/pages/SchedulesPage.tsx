import React, { useEffect, useState } from 'react';
import { Calendar, Plus, Trash2 } from 'lucide-react';
import { backupApi } from '../api/backupApi';
import type { BackupSchedule } from '../types';

export const SchedulesPage = () => {
  const [schedules, setSchedules] = useState<BackupSchedule[]>([]);
  const [, setLoading] = useState(true);

  useEffect(() => {
    backupApi.getSchedules().then((res: { data: BackupSchedule[] }) => {
      setSchedules(res.data);
      setLoading(false);
    });
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this schedule?')) return;
    try {
      await backupApi.deleteSchedule(id);
      setSchedules(schedules.filter(s => s.id !== id));
    } catch {
      alert('Failed to delete schedule');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Backup Schedules</h1>
          <p className="text-slate-500">Automate your backup routines with custom timing and retention.</p>
        </div>
        <button className="flex items-center space-x-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">
          <Plus className="h-4 w-4" />
          <span>New Schedule</span>
        </button>
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        {schedules.map((schedule) => (
          <div key={schedule.id} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex justify-between items-start mb-4">
              <div className="rounded-lg bg-indigo-50 p-2 text-indigo-600">
                <Calendar className="h-6 w-6" />
              </div>
              <button 
                onClick={() => handleDelete(schedule.id)}
                className="text-slate-400 hover:text-red-600 transition-colors"
              >
                <Trash2 className="h-5 w-5" />
              </button>
            </div>
            
            <h3 className="text-lg font-bold text-slate-900">{schedule.name}</h3>
            <p className="text-xs text-slate-500 uppercase tracking-widest font-bold mt-1">{schedule.type} Backup</p>
            
            <div className="mt-4 space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">Frequency</span>
                <span className="font-mono font-bold text-slate-900">{schedule.cron_expression}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">Retention</span>
                <span className="text-slate-900">{schedule.retention_days} Days</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-slate-500">Storage</span>
                <span className="text-slate-900">{schedule.storage_provider_name}</span>
              </div>
            </div>

            <div className="mt-6 flex items-center justify-between">
              <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                schedule.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'
              }`}>
                {schedule.is_active ? 'Active' : 'Paused'}
              </span>
              <span className="text-[10px] text-slate-400">Next run: {schedule.next_run_at ? new Date(schedule.next_run_at).toLocaleString() : 'N/A'}</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
