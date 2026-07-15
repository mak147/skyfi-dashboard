import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { ActionCatalogPage } from './pages/ActionCatalogPage';
import { TriggerCatalogPage } from './pages/TriggerCatalogPage';
import { WorkflowBuilderPage } from './pages/WorkflowBuilderPage';
import { WorkflowDashboardPage } from './pages/WorkflowDashboardPage';
import { WorkflowHistoryPage } from './pages/WorkflowHistoryPage';
import { WorkflowListPage } from './pages/WorkflowListPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) {
    return <div className="h-72 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const WorkflowRoutes = () => (
  <Routes>
    <Route
      index
      element={
        <Gate permission="workflow.view">
          <WorkflowDashboardPage />
        </Gate>
      }
    />
    <Route
      path="list"
      element={
        <Gate permission="workflow.view">
          <WorkflowListPage />
        </Gate>
      }
    />
    <Route
      path="new"
      element={
        <Gate permission="workflow.create">
          <WorkflowBuilderPage />
        </Gate>
      }
    />
    <Route
      path=":id/edit"
      element={
        <Gate permission="workflow.update">
          <WorkflowBuilderPage />
        </Gate>
      }
    />
    <Route
      path="history"
      element={
        <Gate permission="workflow.view">
          <WorkflowHistoryPage />
        </Gate>
      }
    />
    <Route
      path="triggers"
      element={
        <Gate permission="workflow.view">
          <TriggerCatalogPage />
        </Gate>
      }
    />
    <Route
      path="actions"
      element={
        <Gate permission="workflow.view">
          <ActionCatalogPage />
        </Gate>
      }
    />
    <Route path="*" element={<Navigate to="/workflows" replace />} />
  </Routes>
);
