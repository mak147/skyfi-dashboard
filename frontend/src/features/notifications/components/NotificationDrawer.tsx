import { Link } from 'react-router-dom';

import { Button } from '@/components/ui/button';

import { NotificationItemRow } from './NotificationItem';
import type { NotificationItem } from '../types';

export const NotificationDrawer = ({
  open,
  onClose,
  items,
  isLoading,
  onRead,
  onMarkAll,
  isMarkingAll,
}: {
  open: boolean;
  onClose: () => void;
  items: NotificationItem[];
  isLoading?: boolean;
  onRead?: (id: number) => void;
  onMarkAll?: () => void;
  isMarkingAll?: boolean;
}) => {
  if (!open) return null;

  return (
    <>
      <div className="fixed inset-0 z-40 bg-slate-950/40" aria-hidden onClick={onClose} />
      <aside
        className="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        aria-label="Notifications drawer"
      >
        <header className="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Inbox</p>
            <h2 className="text-lg font-bold text-slate-900 dark:text-white">Notifications</h2>
          </div>
          <div className="flex items-center gap-2">
            {onMarkAll ? (
              <Button size="sm" variant="secondary" disabled={isMarkingAll} onClick={onMarkAll}>
                Mark all read
              </Button>
            ) : null}
            <button
              type="button"
              className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 dark:border-slate-700"
              onClick={onClose}
              aria-label="Close notifications"
            >
              ✕
            </button>
          </div>
        </header>

        <div className="flex-1 space-y-3 overflow-y-auto p-4">
          {isLoading
            ? Array.from({ length: 4 }).map((_, i) => (
                <div key={i} className="h-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />
              ))
            : null}
          {!isLoading && items.length === 0 ? (
            <p className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
              You are all caught up.
            </p>
          ) : null}
          {!isLoading
            ? items.map((item) => (
                <NotificationItemRow key={item.id} item={item} onRead={onRead} compact />
              ))
            : null}
        </div>

        <footer className="border-t border-slate-200 p-4 dark:border-slate-700">
          <Link
            to="/notifications"
            onClick={onClose}
            className="flex h-10 items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-500"
          >
            Open notification center
          </Link>
        </footer>
      </aside>
    </>
  );
};
