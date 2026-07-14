import { Routes, Route, Navigate } from 'react-router-dom';
import { usePermissions } from '@/hooks/usePermissions';
import { ConnectionsListPage } from './pages/ConnectionsListPage';
import { ConnectionDetailPage } from './pages/ConnectionDetailPage';
import { CreateConnectionPage } from './pages/CreateConnectionPage';
import { EditConnectionPage } from './pages/EditConnectionPage';

const Gate = ({ permission, children }: { permission: string; children: React.ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-40 animate-pulse rounded-xl bg-slate-100" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const ConnectionRoutes = () => (
  <Routes>
    <Route
      path="/"
      element={
        <Gate permission="connections.view">
          <ConnectionsListPage />
        </Gate>
      }
    />
    <Route
      path="/new"
      element={
        <Gate permission="connections.create">
          <CreateConnectionPage />
        </Gate>
      }
    />
    <Route
      path="/:id"
      element={
        <Gate permission="connections.view">
          <ConnectionDetailPage />
        </Gate>
      }
    />
    <Route
      path="/:id/edit"
      element={
        <Gate permission="connections.update">
          <EditConnectionPage />
        </Gate>
      }
    />
  </Routes>
);
