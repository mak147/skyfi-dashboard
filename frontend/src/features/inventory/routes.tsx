import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { usePermissions } from '@/hooks/usePermissions';
import { AssetDetailPage } from './pages/AssetDetailPage';
import { AssetsPage } from './pages/AssetsPage';
import { CategoriesPage } from './pages/CategoriesPage';
import { InventoryDashboardPage } from './pages/InventoryDashboardPage';
import { ProductsPage } from './pages/ProductsPage';
import { StockMovementsPage } from './pages/StockMovementsPage';
import { TransfersPage } from './pages/TransfersPage';
import { WarehousesPage } from './pages/WarehousesPage';

const Gate = ({ permission, children }: { permission: string; children: ReactNode }) => {
  const { can, isLoading } = usePermissions();
  if (isLoading) return <div className="h-72 animate-pulse rounded-xl bg-slate-200" />;
  return can(permission) ? <>{children}</> : <Navigate to="/dashboard" replace />;
};

export const InventoryRoutes = () => <Routes>
  <Route index element={<Gate permission="inventory.view"><InventoryDashboardPage /></Gate>} />
  <Route path="products" element={<Gate permission="inventory.view"><ProductsPage /></Gate>} />
  <Route path="assets" element={<Gate permission="inventory.view"><AssetsPage /></Gate>} />
  <Route path="assets/:id" element={<Gate permission="inventory.view"><AssetDetailPage /></Gate>} />
  <Route path="warehouses" element={<Gate permission="inventory.view"><WarehousesPage /></Gate>} />
  <Route path="stock-movements" element={<Gate permission="inventory.view"><StockMovementsPage /></Gate>} />
  <Route path="transfers" element={<Gate permission="inventory.view"><TransfersPage /></Gate>} />
  <Route path="categories" element={<Gate permission="inventory.view"><CategoriesPage /></Gate>} />
  <Route path="*" element={<Navigate to="/inventory" replace />} />
</Routes>;
