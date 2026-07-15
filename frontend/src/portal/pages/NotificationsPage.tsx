import { NotificationPanel } from '../components/NotificationPanel';

export const NotificationsPage = () => (
  <div className="space-y-6">
    <div>
      <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Notifications</h1>
      <p className="mt-1 text-sm text-slate-500">Stay up to date with your service and billing.</p>
    </div>
    <NotificationPanel />
  </div>
);
