import { clsx } from 'clsx';
import { Link } from 'react-router-dom';

import type { NotificationItem as Item } from '../types';

const severityDot: Record<string, string> = {
  info: 'bg-sky-500',
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  critical: 'bg-rose-500',
};

export const NotificationItemRow = ({
  item,
  onRead,
  onArchive,
  compact = false,
}: {
  item: Item;
  onRead?: (id: number) => void;
  onArchive?: (id: number) => void;
  compact?: boolean;
}) => {
  const unread = item.status === 'unread';

  return (
    <article
      className={clsx(
        'rounded-xl border p-4 transition dark:border-slate-700',
        unread
          ? 'border-indigo-200 bg-indigo-50/60 dark:border-indigo-900 dark:bg-indigo-950/30'
          : 'border-slate-200 bg-white dark:bg-slate-900',
      )}
    >
      <div className="flex items-start gap-3">
        <span className={clsx('mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full', severityDot[item.severity] ?? severityDot.info)} />
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className={clsx('text-sm', unread ? 'font-bold text-slate-900 dark:text-white' : 'font-semibold text-slate-800 dark:text-slate-100')}>
              {item.title}
            </h3>
            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800">
              {item.category}
            </span>
          </div>
          <p className={clsx('mt-1 text-sm text-slate-600 dark:text-slate-300', compact && 'line-clamp-2')}>{item.body}</p>
          <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-400">
            <time dateTime={item.created_at}>{new Date(item.created_at).toLocaleString()}</time>
            {item.action_url ? (
              <Link to={item.action_url} className="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                Open
              </Link>
            ) : null}
            {unread && onRead ? (
              <button type="button" className="font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300" onClick={() => onRead(item.id)}>
                Mark read
              </button>
            ) : null}
            {onArchive ? (
              <button type="button" className="font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400" onClick={() => onArchive(item.id)}>
                Archive
              </button>
            ) : null}
          </div>
        </div>
      </div>
    </article>
  );
};
