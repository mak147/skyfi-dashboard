import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { ActivityDashboardPage } from './pages/ActivityDashboardPage';
import { AuditLogsPage } from './pages/AuditLogsPage';
import { ComplianceCenterPage } from './pages/ComplianceCenterPage';
import { RetentionPoliciesPage } from './pages/RetentionPoliciesPage';
import { UserActivityPage } from './pages/UserActivityPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) {
    return <div className="h-72 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const AuditRoutes = () => (
  <Routes>
    <Route
      index
      element={
        <Gate permission="audit.view">
          <ActivityDashboardPage />
        </Gate>
      }
    />
    <Route
      path="logs"
      element={
        <Gate permission="audit.view">
          <AuditLogsPage />
        </Gate>
      }
    />
    <Route
      path="users/:id/activity"
      element={
        <Gate permission="audit.view">
          <UserActivityPage />
        </Gate>
      }
    />
    <Route
      path="compliance"
      element={
        <Gate permission="compliance.manage">
          <ComplianceCenterPage />
        </Gate>
      }
    />
    <Route
      path="retention"
      element={
        <Gate permission="compliance.manage">
          <RetentionPoliciesPage />
        </Gate>
      }
    />
    <Route path="*" element={<Navigate to="/audit" replace />} />
  </Routes>
);
