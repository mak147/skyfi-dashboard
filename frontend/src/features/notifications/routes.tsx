import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { DeliveryHistoryPage } from './pages/DeliveryHistoryPage';
import { NotificationCenterPage } from './pages/NotificationCenterPage';
import { NotificationTemplatesPage } from './pages/NotificationTemplatesPage';
import { UserPreferencesPage } from './pages/UserPreferencesPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) {
    return <div className="h-72 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const NotificationRoutes = () => (
  <Routes>
    <Route
      index
      element={
        <Gate permission="notifications.view">
          <NotificationCenterPage />
        </Gate>
      }
    />
    <Route
      path="templates"
      element={
        <Gate permission="notifications.templates">
          <NotificationTemplatesPage />
        </Gate>
      }
    />
    <Route
      path="preferences"
      element={
        <Gate permission="notifications.preferences">
          <UserPreferencesPage />
        </Gate>
      }
    />
    <Route
      path="deliveries"
      element={
        <Gate permission="notifications.manage">
          <DeliveryHistoryPage />
        </Gate>
      }
    />
    <Route path="*" element={<Navigate to="/notifications" replace />} />
  </Routes>
);
