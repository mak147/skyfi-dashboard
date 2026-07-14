import type { PppoeAccountStatistics } from '../types';

interface UsageStatisticsProps {
  stats: PppoeAccountStatistics | undefined;
  isLoading: boolean;
}

const formatBytes = (bytes = 0) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const formatUptime = (seconds = 0) => {
  if (seconds === 0) return '0s';
  const days = Math.floor(seconds / (3600 * 24));
  const hrs = Math.floor((seconds % (3600 * 24)) / 3600);
  const mins = Math.floor((seconds % 3600) / 60);
  if (days > 0) return `${days}d ${hrs}h ${mins}m`;
  if (hrs > 0) return `${hrs}h ${mins}m`;
  return `${mins}m ${seconds % 60}s`;
};

export const UsageStatistics = ({ stats, isLoading }: UsageStatisticsProps) => {
  if (isLoading || !stats) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 animate-pulse">
        {Array.from({ length: 4 }).map((_, idx) => (
          <div key={idx} className="h-24 rounded-xl bg-slate-100 p-4" />
        ))}
      </div>
    );
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Download (RX)</p>
        <p className="mt-2 text-2xl font-bold tracking-tight text-indigo-600 font-mono">
          {formatBytes(stats.total_bytes_in)}
        </p>
        <p className="mt-1 text-xs text-slate-500">Total bytes received by server</p>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Upload (TX)</p>
        <p className="mt-2 text-2xl font-bold tracking-tight text-emerald-600 font-mono">
          {formatBytes(stats.total_bytes_out)}
        </p>
        <p className="mt-1 text-xs text-slate-500">Total bytes transmitted by server</p>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Cumulative Uptime</p>
        <p className="mt-2 text-2xl font-bold tracking-tight text-slate-800 font-mono">
          {formatUptime(stats.total_uptime_seconds)}
        </p>
        <p className="mt-1 text-xs text-slate-500">Across all recorded sessions</p>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Session History Count</p>
        <p className="mt-2 text-2xl font-bold tracking-tight text-slate-800 font-mono">
          {stats.session_count}
        </p>
        <p className="mt-1 text-xs text-slate-500">Recorded dial-in / disconnect events</p>
      </div>
    </div>
  );
};
