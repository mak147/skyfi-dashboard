import { NotificationItemRow } from './NotificationItem';
import type { NotificationItem } from '../types';

export const NotificationList = ({
  items,
  isLoading,
  onRead,
  onArchive,
  emptyMessage = 'No notifications yet.',
}: {
  items: NotificationItem[];
  isLoading?: boolean;
  onRead?: (id: number) => void;
  onArchive?: (id: number) => void;
  emptyMessage?: string;
}) => {
  if (isLoading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-24 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
        ))}
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">
        {emptyMessage}
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {items.map((item) => (
        <NotificationItemRow key={item.id} item={item} onRead={onRead} onArchive={onArchive} />
      ))}
    </div>
  );
};
