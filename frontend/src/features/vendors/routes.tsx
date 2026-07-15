import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { usePermissions } from '@/hooks/usePermissions';
import { SupplierDashboardPage } from './pages/SupplierDashboardPage';
import { SupplierListPage } from './pages/SupplierListPage';
import { SupplierDetailsPage } from './pages/SupplierDetailsPage';
import { ContactsPage } from './pages/ContactsPage';
import { ContractsPage } from './pages/ContractsPage';
import { QuotationsPage } from './pages/QuotationsPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-72 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const VendorRoutes = () => (
  <Routes>
    <Route index element={<Gate permission="vendors.view"><SupplierDashboardPage /></Gate>} />
    <Route path="list" element={<Gate permission="vendors.view"><SupplierListPage /></Gate>} />
    <Route path="contacts" element={<Gate permission="vendors.view"><ContactsPage /></Gate>} />
    <Route path="contracts" element={<Gate permission="vendors.contracts"><ContractsPage /></Gate>} />
    <Route path="quotations" element={<Gate permission="vendors.view"><QuotationsPage /></Gate>} />
    <Route path=":id" element={<Gate permission="vendors.view"><SupplierDetailsPage /></Gate>} />
    <Route path="*" element={<Navigate to="/purchasing/vendors" replace />} />
  </Routes>
);
