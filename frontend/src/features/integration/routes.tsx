import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { ConnectorsPage } from './pages/ConnectorsPage';
import { DeliveryHistoryPage } from './pages/DeliveryHistoryPage';
import { EventExplorerPage } from './pages/EventExplorerPage';
import { IntegrationDashboardPage } from './pages/IntegrationDashboardPage';
import { ApiKeysPage } from './pages/ApiKeysPage';
import { ClientApplicationsPage } from './pages/ClientApplicationsPage';
import { WebhooksPage } from './pages/WebhooksPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) {
    return <div className="h-72 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const IntegrationRoutes = () => (
  <Routes>
    <Route index element={<Gate permission="integration.view"><IntegrationDashboardPage /></Gate>} />
    <Route path="api-keys" element={<Gate permission="integration.apikeys"><ApiKeysPage /></Gate>} />
    <Route path="applications" element={<Gate permission="integration.manage"><ClientApplicationsPage /></Gate>} />
    <Route path="webhooks" element={<Gate permission="integration.webhooks"><WebhooksPage /></Gate>} />
    <Route path="deliveries" element={<Gate permission="integration.webhooks"><DeliveryHistoryPage /></Gate>} />
    <Route path="events" element={<Gate permission="integration.view"><EventExplorerPage /></Gate>} />
    <Route path="connectors" element={<Gate permission="integration.view"><ConnectorsPage /></Gate>} />
    <Route path="*" element={<Navigate to="/integration" replace />} />
  </Routes>
);
