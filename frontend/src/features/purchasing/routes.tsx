import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { usePermissions } from '@/hooks/usePermissions';
import { PurchasingDashboardPage } from './pages/PurchasingDashboardPage';
import { PurchaseRequestsPage } from './pages/PurchaseRequestsPage';
import { PurchaseOrderListPage } from './pages/PurchaseOrderListPage';
import { GoodsReceiptsPage } from './pages/GoodsReceiptsPage';
import { SupplierInvoicesPage } from './pages/SupplierInvoicesPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-72 animate-pulse rounded-xl bg-slate-200" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const PurchasingRoutes = () => <Routes>
  <Route index element={<Gate permission="purchasing.view"><PurchasingDashboardPage /></Gate>} />
  <Route path="requests" element={<Gate permission="purchasing.view"><PurchaseRequestsPage /></Gate>} />
  <Route path="orders" element={<Gate permission="purchasing.view"><PurchaseOrderListPage /></Gate>} />
  <Route path="receipts" element={<Gate permission="purchasing.view"><GoodsReceiptsPage /></Gate>} />
  <Route path="invoices" element={<Gate permission="purchasing.view"><SupplierInvoicesPage /></Gate>} />
  <Route path="*" element={<Navigate to="/purchasing" replace />} />
</Routes>;
