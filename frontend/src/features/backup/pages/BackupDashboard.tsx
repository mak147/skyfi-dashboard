import React, { useEffect, useState } from 'react';
import { Play, Calendar, Shield, History, RefreshCcw } from 'lucide-react';
import { backupApi } from '../api/backupApi';
import { BackupStatistics } from '../components/BackupStatistics';
import { BackupTable } from '../components/BackupTable';
import type { BackupStatistics as Stats, BackupJob } from '../types';

export const BackupDashboard = () => {
  const [stats, setStats] = useState<Stats | null>(null);
  const [recentJobs, setRecentJobs] = useState<BackupJob[]>([]);
  const [loading, setLoading] = useState(true);

  const loadData = async () => {
    try {
      const [statsRes, jobsRes] = await Promise.all([
        backupApi.getStatistics(),
        backupApi.getJobs({ perPage: 5 })
      ]);
      setStats(statsRes.data);
      setRecentJobs(jobsRes.data.items);
    } catch {
      console.error('Failed to load backup dashboard data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleManualBackup = async (type: string) => {
    try {
      await backupApi.runManualBackup(type);
      loadData();
    } catch {
      alert('Failed to trigger manual backup');
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Backup & Recovery</h1>
          <p className="text-slate-500">Manage system backups, scheduling, and disaster recovery plans.</p>
        </div>
        <div className="flex space-x-3">
          <button 
            onClick={() => handleManualBackup('full')}
            className="flex items-center space-x-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm"
          >
            <Play className="h-4 w-4" />
            <span>Run Full Backup</span>
          </button>
          <button 
             onClick={loadData}
             className="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-50 shadow-sm"
          >
            <RefreshCcw className={`h-5 w-5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      <BackupStatistics stats={stats} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-lg font-bold text-slate-900">Recent Backup Jobs</h2>
              <a href="/backup/jobs" className="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View All</a>
            </div>
            <BackupTable jobs={recentJobs} />
          </div>
        </div>

        <div className="space-y-6">
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-bold text-slate-900">Quick Actions</h2>
            <div className="grid grid-cols-1 gap-3">
              <button className="flex items-center space-x-3 rounded-lg border border-slate-100 bg-slate-50 p-3 text-left hover:bg-slate-100 transition-colors">
                <Calendar className="h-5 w-5 text-indigo-600" />
                <div>
                  <p className="text-sm font-bold text-slate-900">Manage Schedules</p>
                  <p className="text-xs text-slate-500">Configure automated backup routines</p>
                </div>
              </button>
              <button className="flex items-center space-x-3 rounded-lg border border-slate-100 bg-slate-50 p-3 text-left hover:bg-slate-100 transition-colors">
                <Shield className="h-5 w-5 text-green-600" />
                <div>
                  <p className="text-sm font-bold text-slate-900">Verify Integrity</p>
                  <p className="text-xs text-slate-500">Run checksum validation on files</p>
                </div>
              </button>
              <button className="flex items-center space-x-3 rounded-lg border border-slate-100 bg-slate-50 p-3 text-left hover:bg-slate-100 transition-colors">
                <History className="h-5 w-5 text-purple-600" />
                <div>
                  <p className="text-sm font-bold text-slate-900">Restore Center</p>
                  <p className="text-xs text-slate-500">Restore data from available backups</p>
                </div>
              </button>
            </div>
          </div>
          
          <div className="rounded-xl border border-amber-100 bg-amber-50 p-6 shadow-sm">
            <h2 className="mb-2 text-lg font-bold text-amber-900">System Health</h2>
            <p className="text-sm text-amber-700 mb-4">Last full backup was completed successfully 4 hours ago.</p>
            <div className="flex items-center space-x-2">
              <div className="h-2 w-full rounded-full bg-amber-200">
                <div className="h-2 w-3/4 rounded-full bg-green-500"></div>
              </div>
              <span className="text-xs font-bold text-amber-900">Optimal</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
