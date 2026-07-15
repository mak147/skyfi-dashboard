import React, { useEffect, useState } from 'react';
import { backupApi } from '../api/backupApi';
import { BackupTable } from '../components/BackupTable';
import type { BackupJob } from '../types';

export const BackupJobsPage = () => {
  const [jobs, setJobs] = useState<BackupJob[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    backupApi.getJobs({ perPage: 50 }).then((res: { data: { items: BackupJob[]; total: number } }) => {
      setJobs(res.data.items);
      setLoading(false);
    });
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Backup Jobs</h1>
        <p className="text-slate-500">History of all automated and manual backup executions.</p>
      </div>
      
      {loading ? (
        <div className="animate-pulse space-y-4">
          {[...Array(5)].map((_, i) => (
            <div key={i} className="h-16 rounded-xl bg-slate-100" />
          ))}
        </div>
      ) : (
        <BackupTable jobs={jobs} />
      )}
    </div>
  );
};
