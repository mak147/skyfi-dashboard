import { Button } from '@/components/ui/button';
import type { PppoeActiveSession } from '../types';

interface ActiveSessionTableProps {
  sessions: PppoeActiveSession[];
  isLoading: boolean;
  canManage: boolean;
  onDisconnect: (session: PppoeActiveSession) => void;
}

export const ActiveSessionTable = ({ sessions, isLoading, canManage, onDisconnect }: ActiveSessionTableProps) => (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200">
          <thead className="bg-slate-50">
            <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3">Router</th>
              <th className="px-4 py-3">Username & Customer</th>
              <th className="px-4 py-3">Session IP</th>
              <th className="px-4 py-3">Caller ID / MAC</th>
              <th className="px-4 py-3">Uptime</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {isLoading ? (
              Array.from({ length: 4 }).map((_, idx) => (
                <tr key={idx}>
                  <td colSpan={6} className="px-4 py-5">
                    <div className="h-5 animate-pulse rounded bg-slate-100" />
                  </td>
                </tr>
              ))
            ) : null}
            {!isLoading && sessions.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-sm text-slate-500">
                  No active PPPoE sessions found on selected routers right now.
                </td>
              </tr>
            ) : null}
            {!isLoading &&
              sessions.map((session) => (
                <tr key={`${session.router_id}-${session.id}-${session.username}`} className="hover:bg-slate-50">
                  <td className="px-4 py-3 text-sm font-medium text-slate-800">
                    {session.router_name ?? `Router #${session.router_id}`}
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-slate-900">{session.username}</p>
                    <p className="text-xs text-slate-500">{session.service}</p>
                  </td>
                  <td className="px-4 py-3 font-mono text-sm text-emerald-600 font-medium">
                    {session.ip_address ?? '—'}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-600">
                    {session.caller_id ?? '—'}
                  </td>
                  <td className="px-4 py-3 font-semibold text-sm text-slate-800">
                    {session.uptime}
                  </td>
                  <td className="px-4 py-3 text-right">
                    {canManage ? (
                      <Button
                        size="sm"
                        variant="secondary"
                        className="text-red-700 hover:bg-red-50"
                        onClick={() => onDisconnect(session)}
                      >
                        Disconnect
                      </Button>
                    ) : null}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
);
