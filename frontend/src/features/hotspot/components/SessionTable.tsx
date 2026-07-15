import type { HotspotActiveSession } from '../types';

interface SessionTableProps {
  sessions: HotspotActiveSession[];
  isLoading: boolean;
  canDisconnect: boolean;
  onDisconnect: (routerId: number, sessionId: string) => void;
  onForceLogout: (username: string) => void;
}

const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

export const SessionTable = ({ sessions, isLoading, canDisconnect, onDisconnect, onForceLogout }: SessionTableProps) => (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3">Username</th>
              <th className="px-4 py-3">Router</th>
              <th className="px-4 py-3">MAC Address</th>
              <th className="px-4 py-3">IP Address</th>
              <th className="px-4 py-3">Uptime</th>
              <th className="px-4 py-3">Download</th>
              <th className="px-4 py-3">Upload</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {isLoading
              ? Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td colSpan={8} className="px-4 py-5">
                      <div className="h-5 animate-pulse rounded bg-slate-100" />
                    </td>
                  </tr>
                ))
              : null}
            {!isLoading && sessions.length === 0 ? (
              <tr>
                <td colSpan={8} className="px-4 py-12 text-center text-sm text-slate-500">
                  <div className="flex flex-col items-center gap-2">
                    <span className="text-3xl">📡</span>
                    <p>No active hotspot sessions found.</p>
                    <p className="text-xs text-slate-400">Sessions auto-refresh every 15 seconds.</p>
                  </div>
                </td>
              </tr>
            ) : null}
            {!isLoading &&
              sessions.map((session) => (
                <tr key={session.id} className="transition hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <span className="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                      <span className="font-semibold text-slate-900">{session.username}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-sm text-slate-600">
                    {session.router_name ?? `Router #${session.router_id}`}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-600">{session.mac_address ?? '—'}</td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-600">{session.ip_address ?? '—'}</td>
                  <td className="px-4 py-3">
                    <span className="text-sm font-semibold text-indigo-700">{session.uptime}</span>
                  </td>
                  <td className="px-4 py-3 text-sm text-slate-600">{formatBytes(session.bytes_in)}</td>
                  <td className="px-4 py-3 text-sm text-slate-600">{formatBytes(session.bytes_out)}</td>
                  <td className="px-4 py-3 text-right">
                    {canDisconnect ? (
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          type="button"
                          className="rounded-md bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition"
                          onClick={() => onDisconnect(session.router_id, session.id)}
                        >
                          Disconnect
                        </button>
                        <button
                          type="button"
                          className="rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition"
                          onClick={() => onForceLogout(session.username)}
                        >
                          Force Logout
                        </button>
                      </div>
                    ) : null}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
  );
