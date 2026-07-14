import type { RouterHealth } from '../types';

const bytes = (value: number | null) => {
  if (value === null) return 'Unavailable';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let current = value;
  let unit = 0;
  while (current >= 1024 && unit < units.length - 1) { current /= 1024; unit += 1; }
  return `${current.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
};

const percentUsed = (total: number | null, free: number | null) => {
  if (!total || free === null) return 'Unavailable';
  return `${Math.max(0, Math.min(100, ((total - free) / total) * 100)).toFixed(1)}% used`;
};

export const RouterStatistics = ({ health }: { health: RouterHealth | null }) => {
  if (!health) return <div className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No health snapshot yet. Run a health check to collect router metrics.</div>;
  const stats = [
    ['Latency', health.latency_ms === null ? 'Unavailable' : `${health.latency_ms.toFixed(1)} ms`],
    ['CPU', health.cpu_usage_percent === null ? 'Unavailable' : `${health.cpu_usage_percent.toFixed(1)}%`],
    ['Memory', percentUsed(health.memory_total_bytes, health.memory_free_bytes)],
    ['Disk', percentUsed(health.disk_total_bytes, health.disk_free_bytes)],
    ['Temperature', health.temperature_celsius === null ? 'Unavailable' : `${health.temperature_celsius.toFixed(1)} °C`],
    ['Active users', health.active_users_count?.toLocaleString() ?? 'Unavailable'],
    ['Simple queues', health.queue_count?.toLocaleString() ?? 'Unavailable'],
    ['Traffic counters', `${bytes(health.traffic_rx_bytes)} ↓ / ${bytes(health.traffic_tx_bytes)} ↑`],
  ];

  return <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{stats.map(([label, value]) => <section key={label} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-2 truncate text-lg font-bold tabular-nums text-slate-900" title={value}>{value}</p></section>)}</div>;
};
