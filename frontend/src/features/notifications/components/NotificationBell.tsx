import { useState } from 'react';
import { clsx } from 'clsx';

import { usePermissions } from '@/hooks/usePermissions';

import { NotificationDrawer } from './NotificationDrawer';
import { useMarkAllRead, useMarkRead, useNotifications, useUnreadCount } from '../api/useNotifications';

export const NotificationBell = () => {
  const { can, isLoading: permsLoading } = usePermissions();
  const [open, setOpen] = useState(false);
  const unread = useUnreadCount();
  const list = useNotifications({ status: 'unread', per_page: 8, page: 1 });
  const markRead = useMarkRead();
  const markAll = useMarkAllRead();

  if (permsLoading || !can('notifications.view')) {
    return null;
  }

  const count = unread.data ?? 0;
  const items = list.data?.data.map((row) => row.attributes) ?? [];

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className={clsx(
          'relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800',
        )}
        aria-label={count > 0 ? `${count} unread notifications` : 'Notifications'}
        title="Notifications"
      >
        <span aria-hidden className="text-lg">
          🔔
        </span>
        {count > 0 ? (
          <span className="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
            {count > 99 ? '99+' : count}
          </span>
        ) : null}
      </button>

      <NotificationDrawer
        open={open}
        onClose={() => setOpen(false)}
        items={items}
        isLoading={list.isLoading}
        onRead={(id) => markRead.mutate(id)}
        onMarkAll={() => markAll.mutate()}
        isMarkingAll={markAll.isPending}
      />
    </>
  );
};
