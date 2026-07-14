import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { ActiveSessionsPage } from './pages/ActiveSessionsPage';
import { CreateUserPage } from './pages/CreateUserPage';
import { EditUserPage } from './pages/EditUserPage';
import { ImportUsersPage } from './pages/ImportUsersPage';
import { PPPoEDetailsPage } from './pages/PPPoEDetailsPage';
import { PPPoEUsersPage } from './pages/PPPoEUsersPage';
import { SynchronizationPage } from './pages/SynchronizationPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-48 animate-pulse rounded-xl bg-slate-100" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const PppoeRoutes = () => (
  <Routes>
    <Route path="/" element={<Gate permission="pppoe.view"><PPPoEUsersPage /></Gate>} />
    <Route path="/accounts/new" element={<Gate permission="pppoe.create"><CreateUserPage /></Gate>} />
    <Route path="/accounts/:id" element={<Gate permission="pppoe.view"><PPPoEDetailsPage /></Gate>} />
    <Route path="/accounts/:id/edit" element={<Gate permission="pppoe.update"><EditUserPage /></Gate>} />
    <Route path="/sessions/active" element={<Gate permission="pppoe.monitor"><ActiveSessionsPage /></Gate>} />
    <Route path="/sync" element={<Gate permission="pppoe.sync"><SynchronizationPage /></Gate>} />
    <Route path="/import" element={<Gate permission="pppoe.sync"><ImportUsersPage /></Gate>} />
  </Routes>
);
