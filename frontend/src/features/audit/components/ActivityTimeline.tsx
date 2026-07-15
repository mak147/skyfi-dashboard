import type { ActivityEvent } from '../types';

const moduleIcons: Record<string, string> = {
  billing: '🧾',
  payments: '💳',
  finance: '📊',
  customers: '👤',
  connections: '⚡',
  support: '🎧',
  inventory: '▦',
  purchasing: '◈',
  vendors: '◆',
  mikrotik: '◉',
  pppoe: '⇋',
  hotspot: '📶',
  monitoring: '📡',
  field_service: '⚒',
  authentication: '🔐',
  rbac: '◇',
  system: '⚙',
};

interface ActivityTimelineProps {
  items: ActivityEvent[];
  isLoading?: boolean;
}

export const ActivityTimeline = ({ items, isLoading }: ActivityTimelineProps) => {
  if (isLoading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="h-12 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
        ))}
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">
        No activity events found.
      </div>
    );
  }

  return (
    <div className="relative">
      <div className="absolute bottom-0 left-5 top-0 w-px bg-slate-200 dark:bg-slate-700" />
      <div className="space-y-1">
        {items.map((item) => {
          const icon = moduleIcons[item.module] ?? '◎';
          const time = new Date(item.created_at);
          const isRecent = Date.now() - time.getTime() < 3600000;

          return (
            <div key={item.id} className="relative flex items-start gap-3 px-2 py-2">
              <div className={`relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm ${isRecent ? 'bg-indigo-100 ring-2 ring-indigo-400 dark:bg-indigo-900/40 dark:ring-indigo-600' : 'bg-slate-100 dark:bg-slate-800'}`}>
                {icon}
              </div>
              <div className="min-w-0 flex-1 pt-1">
                <div className="flex items-center gap-2">
                  <span className="text-sm font-semibold text-slate-800 dark:text-slate-100">{item.user_name ?? 'System'}</span>
                  <span className="text-xs text-slate-500 dark:text-slate-400">{item.action}</span>
                  <span className="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">{item.module}</span>
                </div>
                <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                  {item.resource_type}{item.resource_id ? ` #${item.resource_id}` : ''} · {time.toLocaleString()}
                  {item.ip_address && <span className="ml-2 font-mono">{item.ip_address}</span>}
                </p>
                {item.description && (
                  <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-300">{item.description}</p>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
