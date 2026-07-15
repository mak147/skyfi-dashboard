import { Navigate, Route, Routes } from 'react-router-dom';

import { AuthenticationRoutes } from '@/features/authentication/routes';
import { ConnectionRoutes } from '@/features/connections/routes';
import { CustomerRoutes } from '@/features/customers/routes';
import { DashboardRoutes } from '@/features/dashboard/routes';
import { BillingRoutes } from '@/features/billing/routes';
import { PackageRoutes } from '@/features/packages/routes';
import { PaymentRoutes } from '@/features/payments/routes';
import { FinanceRoutes } from '@/features/finance/routes';
import { RbacRoutes } from '@/features/rbac/routes';
import { MikrotikRoutes } from '@/features/mikrotik/routes';
import { PppoeRoutes } from '@/features/pppoe/routes';
import { HotspotRoutes } from '@/features/hotspot/routes';
import { SupportRoutes } from '@/features/support/routes';
import { InventoryRoutes } from '@/features/inventory/routes';
import { PurchasingRoutes } from '@/features/purchasing/routes';
import { VendorRoutes } from '@/features/vendors/routes';
import { AppLayout } from '@/layouts/AppLayout';
import { ProtectedRoute } from '@/routes/protected-route';

const NotFound = () => (
  <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <section className="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-card">
      <h1 className="text-xl font-semibold text-slate-800">Page not found</h1>
      <p className="mt-2 text-sm text-slate-500">The requested page does not exist.</p>
    </section>
  </main>
);

export const AppRoutes = () => (
  <Routes>
    <Route path="/login" element={<AuthenticationRoutes.login />} />
    <Route element={<ProtectedRoute />}>
      <Route element={<AppLayout />}>
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard" element={<DashboardRoutes.page />} />
        <Route path="/customers/*" element={<CustomerRoutes />} />
        <Route path="/connections/*" element={<ConnectionRoutes />} />
        <Route path="/packages/*" element={<PackageRoutes />} />
        <Route path="/billing/*" element={<BillingRoutes />} />
        <Route path="/payments/*" element={<PaymentRoutes />} />
        <Route path="/finance/*" element={<FinanceRoutes />} />
        <Route path="/network/routers/*" element={<MikrotikRoutes />} />
        <Route path="/network/pppoe/*" element={<PppoeRoutes />} />
        <Route path="/hotspot/*" element={<HotspotRoutes />} />
        <Route path="/support/*" element={<SupportRoutes />} />
        <Route path="/inventory/*" element={<InventoryRoutes />} />
        <Route path="/purchasing/*" element={<PurchasingRoutes />} />
        <Route path="/vendors/*" element={<VendorRoutes />} />
        <Route path="/admin/roles/*" element={<RbacRoutes />} />
      </Route>
    </Route>
    <Route path="*" element={<NotFound />} />
  </Routes>
);
