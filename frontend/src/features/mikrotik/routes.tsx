import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { AddRouterPage } from './pages/AddRouterPage';
import { EditRouterPage } from './pages/EditRouterPage';
import { RouterDetailPage } from './pages/RouterDetailPage';
import { RouterHealthPage } from './pages/RouterHealthPage';
import { RouterListPage } from './pages/RouterListPage';
import { TestConnectionPage } from './pages/TestConnectionPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-48 animate-pulse rounded-xl bg-slate-100" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const MikrotikRoutes = () => (
  <Routes>
    <Route path="/" element={<Gate permission="mikrotik.view"><RouterListPage /></Gate>} />
    <Route path="/new" element={<Gate permission="mikrotik.create"><AddRouterPage /></Gate>} />
    <Route path="/test-connection" element={<Gate permission="mikrotik.connect"><TestConnectionPage /></Gate>} />
    <Route path="/:id" element={<Gate permission="mikrotik.view"><RouterDetailPage /></Gate>} />
    <Route path="/:id/edit" element={<Gate permission="mikrotik.update"><EditRouterPage /></Gate>} />
    <Route path="/:id/health" element={<Gate permission="mikrotik.view"><RouterHealthPage /></Gate>} />
  </Routes>
);
