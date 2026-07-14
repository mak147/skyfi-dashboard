import type { PppoeSessionHistory } from '../types';

interface SessionTimelineProps {
  history: PppoeSessionHistory[];
  isLoading: boolean;
}

const formatBytes = (bytes = 0) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const formatDuration = (seconds = 0) => {
  if (seconds === 0) return '0s';
  const hrs = Math.floor(seconds / 3600);
  const mins = Math.floor((seconds % 3600) / 60);
  if (hrs > 0) return `${hrs}h ${mins}m`;
  return `${mins}m ${seconds % 60}s`;
};

export const SessionTimeline = ({ history, isLoading }: SessionTimelineProps) => (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-slate-200 px-6 py-4">
        <h3 className="text-base font-semibold text-slate-900">Historical Connection Timeline</h3>
        <p className="text-sm text-slate-500">Detailed dial-in logs, bandwidth counters, and termination reasons from RouterOS accounting.</p>
      </div>
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3">Username & IP</th>
              <th className="px-4 py-3">MAC / Caller ID</th>
              <th className="px-4 py-3">Session Duration</th>
              <th className="px-4 py-3">Data Transfer (In / Out)</th>
              <th className="px-4 py-3">Started & Ended</th>
              <th className="px-4 py-3">Disconnect Reason</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 text-sm">
            {isLoading ? (
              Array.from({ length: 4 }).map((_, idx) => (
                <tr key={idx}>
                  <td colSpan={6} className="px-4 py-5">
                    <div className="h-5 animate-pulse rounded bg-slate-100" />
                  </td>
                </tr>
              ))
            ) : null}
            {!isLoading && history.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-slate-500">
                  No historical sessions logged for this account yet.
                </td>
              </tr>
            ) : null}
            {!isLoading &&
              history.map((h) => (
                <tr key={h.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-900">{h.username}</p>
                    <p className="font-mono text-xs text-emerald-600">{h.ip_address}</p>
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-600">
                    <div>{h.mac_address ?? '—'}</div>
                    <div className="text-slate-400">{h.caller_id !== h.mac_address ? h.caller_id : ''}</div>
                  </td>
                  <td className="px-4 py-3 font-semibold text-slate-800">
                    {formatDuration(h.uptime_seconds)}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs">
                    <div className="text-indigo-600">↓ {formatBytes(h.bytes_in)}</div>
                    <div className="text-emerald-600">↑ {formatBytes(h.bytes_out)}</div>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-600">
                    <div>{h.started_at}</div>
                    <div className="text-slate-400">{h.ended_at}</div>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-500">
                    {h.disconnect_reason ?? 'Normal disconnect'}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
);
