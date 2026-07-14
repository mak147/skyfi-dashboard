import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { usePermissions } from '@/hooks/usePermissions';

import { HotspotUsersPage } from './pages/HotspotUsersPage';
import { HotspotProfilesPage } from './pages/HotspotProfilesPage';
import { VouchersPage } from './pages/VouchersPage';
import { GenerateVouchersPage } from './pages/GenerateVouchersPage';
import { ActiveSessionsPage } from './pages/ActiveSessionsPage';
import { SynchronizationPage } from './pages/SynchronizationPage';
import { UserDetailsPage } from './pages/UserDetailsPage';
import { CreateUserPage } from './pages/CreateUserPage';
import { EditUserPage } from './pages/EditUserPage';
import { CreateProfilePage } from './pages/CreateProfilePage';
import { EditProfilePage } from './pages/EditProfilePage';
import { ImportUsersPage } from './pages/ImportUsersPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-48 animate-pulse rounded-xl bg-slate-100" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const HotspotRoutes = () => (
  <Routes>
    <Route path="/" element={<Gate permission="hotspot.view"><HotspotUsersPage /></Gate>} />
    <Route path="/users/new" element={<Gate permission="hotspot.create"><CreateUserPage /></Gate>} />
    <Route path="/users/:id" element={<Gate permission="hotspot.view"><UserDetailsPage /></Gate>} />
    <Route path="/users/:id/edit" element={<Gate permission="hotspot.update"><EditUserPage /></Gate>} />
    <Route path="/users/import" element={<Gate permission="hotspot.sync"><ImportUsersPage /></Gate>} />
    <Route path="/profiles" element={<Gate permission="hotspot.view"><HotspotProfilesPage /></Gate>} />
    <Route path="/profiles/new" element={<Gate permission="hotspot.create"><CreateProfilePage /></Gate>} />
    <Route path="/profiles/:id/edit" element={<Gate permission="hotspot.update"><EditProfilePage /></Gate>} />
    <Route path="/vouchers" element={<Gate permission="hotspot.vouchers"><VouchersPage /></Gate>} />
    <Route path="/vouchers/generate" element={<Gate permission="hotspot.vouchers"><GenerateVouchersPage /></Gate>} />
    <Route path="/sessions/active" element={<Gate permission="hotspot.monitor"><ActiveSessionsPage /></Gate>} />
    <Route path="/sync" element={<Gate permission="hotspot.sync"><SynchronizationPage /></Gate>} />
  </Routes>
);
