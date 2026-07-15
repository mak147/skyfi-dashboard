import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { archiveNotification, getNotifications, markAllNotificationsRead, markNotificationRead } from '../api/portalApi';

export const NotificationPanel = () => {
  const queryClient = useQueryClient();
  const notificationsQuery = useQuery({
    queryKey: ['portal', 'notifications'],
    queryFn: () => getNotifications(1, 50),
    staleTime: 30 * 1000,
  });

  const markReadMutation = useMutation({
    mutationFn: markNotificationRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['portal', 'notifications'] }),
  });

  const markAllReadMutation = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['portal', 'notifications'] }),
  });

  const archiveMutation = useMutation({
    mutationFn: archiveNotification,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['portal', 'notifications'] }),
  });

  if (notificationsQuery.isLoading) {
    return <div className="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }

  if (notificationsQuery.error) {
    return (
      <Alert title="Unable to load notifications">
        {apiErrorMessage(notificationsQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  const notifications = notificationsQuery.data?.data ?? [];
  const unreadCount = notifications.filter((n) => n.attributes.status === 'unread').length;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-slate-600 dark:text-slate-300">
          {unreadCount === 0 ? 'No unread notifications' : `${unreadCount} unread notification${unreadCount === 1 ? '' : 's'}`}
        </p>
        <Button
          variant="secondary"
          size="sm"
          onClick={() => markAllReadMutation.mutate()}
          isLoading={markAllReadMutation.isPending}
          disabled={unreadCount === 0}
        >
          Mark all read
        </Button>
      </div>

      <div className="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white shadow-card dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900">
        {notifications.length === 0 ? (
          <div className="px-4 py-8 text-center text-sm text-slate-500">No notifications yet.</div>
        ) : (
          notifications.map((notification) => {
            const attrs = notification.attributes;
            const isUnread = attrs.status === 'unread';

            return (
              <div
                key={notification.id}
                className={`flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between ${
                  isUnread ? 'bg-indigo-50/30 dark:bg-indigo-950/10' : ''
                }`}
              >
                <div className="min-w-0 flex-1">
                  <p className={`text-sm font-semibold ${isUnread ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300'}`}>
                    {attrs.title}
                  </p>
                  <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">{attrs.body}</p>
                  <p className="mt-1 text-xs text-slate-500">{new Date(attrs.created_at).toLocaleString()}</p>
                </div>
                <div className="flex shrink-0 gap-2">
                  {isUnread && (
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={() => markReadMutation.mutate(Number(notification.id))}
                      isLoading={markReadMutation.isPending}
                    >
                      Mark read
                    </Button>
                  )}
                  {attrs.status !== 'archived' && (
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={() => archiveMutation.mutate(Number(notification.id))}
                      isLoading={archiveMutation.isPending}
                    >
                      Archive
                    </Button>
                  )}
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
};
