import type { HotspotUserStatistics } from '../types';

interface UsageStatisticsProps {
  stats: HotspotUserStatistics | null | undefined;
  isLoading?: boolean;
}

const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatUptime = (seconds: number): string => {
  if (seconds === 0) return '0m';
  const days = Math.floor(seconds / 86400);
  const hours = Math.floor((seconds % 86400) / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);

  const parts: string[] = [];
  if (days > 0) parts.push(`${days}d`);
  if (hours > 0) parts.push(`${hours}h`);
  if (minutes > 0) parts.push(`${minutes}m`);
  return parts.join(' ') || '< 1m';
};

export const UsageStatistics = ({ stats, isLoading }: UsageStatisticsProps) => {
  if (isLoading) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="h-5 w-40 rounded bg-slate-200 animate-pulse mb-4" />
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-16 rounded bg-slate-100 animate-pulse" />
          ))}
        </div>
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 className="text-base font-semibold text-slate-900 mb-4">Usage Statistics</h3>
        <p className="text-sm text-slate-500">No usage data available yet.</p>
      </div>
    );
  }

  const totalBytes = stats.total_bytes_in + stats.total_bytes_out;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h3 className="text-base font-semibold text-slate-900 mb-4">Usage Statistics</h3>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div className="rounded-lg bg-indigo-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-wider text-indigo-600">Total Sessions</p>
          <p className="mt-1 text-2xl font-bold text-indigo-700">{stats.session_count.toLocaleString()}</p>
        </div>
        <div className="rounded-lg bg-emerald-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-wider text-emerald-600">Total Uptime</p>
          <p className="mt-1 text-2xl font-bold text-emerald-700">{formatUptime(stats.total_uptime_seconds)}</p>
        </div>
        <div className="rounded-lg bg-blue-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-wider text-blue-600">Download</p>
          <p className="mt-1 text-2xl font-bold text-blue-700">{formatBytes(stats.total_bytes_in)}</p>
        </div>
        <div className="rounded-lg bg-amber-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-wider text-amber-600">Upload</p>
          <p className="mt-1 text-2xl font-bold text-amber-700">{formatBytes(stats.total_bytes_out)}</p>
        </div>
      </div>
      {totalBytes > 0 ? (
        <div className="mt-4">
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Traffic Distribution</p>
          <div className="flex h-3 overflow-hidden rounded-full bg-slate-100">
            <div
              className="bg-blue-500 transition-all"
              style={{ width: `${((stats.total_bytes_in / totalBytes) * 100).toFixed(1)}%` }}
              title={`Download: ${formatBytes(stats.total_bytes_in)}`}
            />
            <div
              className="bg-amber-500 transition-all"
              style={{ width: `${((stats.total_bytes_out / totalBytes) * 100).toFixed(1)}%` }}
              title={`Upload: ${formatBytes(stats.total_bytes_out)}`}
            />
          </div>
          <div className="flex justify-between mt-1 text-xs text-slate-500">
            <span>↓ {((stats.total_bytes_in / totalBytes) * 100).toFixed(1)}%</span>
            <span>↑ {((stats.total_bytes_out / totalBytes) * 100).toFixed(1)}%</span>
          </div>
        </div>
      ) : null}
    </div>
  );
};
