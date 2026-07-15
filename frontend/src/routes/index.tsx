import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { AppLayout } from '@/layouts/AppLayout';
import { ProtectedRoute } from '@/routes/protected-route';

const LoginPage = lazy(() => import('@/features/authentication/routes/LoginPage').then((m) => ({ default: m.LoginPage })));
const CustomerRoutesLazy = lazy(() => import('@/features/customers/routes'));
const ConnectionRoutesLazy = lazy(() => import('@/features/connections/routes'));
const DashboardPage = lazy(() => import('@/features/dashboard/routes/DashboardPage').then((m) => ({ default: m.DashboardPage })));
const BillingRoutesLazy = lazy(() => import('@/features/billing/routes'));
const PackageRoutesLazy = lazy(() => import('@/features/packages/routes'));
const PaymentRoutesLazy = lazy(() => import('@/features/payments/routes'));
const FinanceRoutesLazy = lazy(() => import('@/features/finance/routes'));
const RbacRoutesLazy = lazy(() => import('@/features/rbac/routes'));
const MikrotikRoutesLazy = lazy(() => import('@/features/mikrotik/routes'));
const PppoeRoutesLazy = lazy(() => import('@/features/pppoe/routes'));
const HotspotRoutesLazy = lazy(() => import('@/features/hotspot/routes'));
const SupportRoutesLazy = lazy(() => import('@/features/support/routes'));
const InventoryRoutesLazy = lazy(() => import('@/features/inventory/routes'));
const PurchasingRoutesLazy = lazy(() => import('@/features/purchasing/routes'));
const VendorRoutesLazy = lazy(() => import('@/features/vendors/routes'));
const FieldServiceRoutesLazy = lazy(() => import('@/features/field-service/routes'));
const ReportRoutesLazy = lazy(() => import('@/features/reports/routes'));
const SystemRoutesLazy = lazy(() => import('@/features/system/routes'));
const NotificationRoutesLazy = lazy(() => import('@/features/notifications/routes'));
const AuditRoutesLazy = lazy(() => import('@/features/audit/routes'));
const IntegrationRoutesLazy = lazy(() => import('@/features/integration/routes'));
const WorkflowRoutesLazy = lazy(() => import('@/features/workflow/routes'));
const PortalRoutesLazy = lazy(() => import('@/portal/routes'));

const PageLoadingFallback = () => (
  <div className="flex min-h-[60vh] items-center justify-center">
    <div className="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
  </div>
);

const NotFound = () => (
  <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <section className="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-card">
      <h1 className="text-xl font-semibold text-slate-800">Page not found</h1>
      <p className="mt-2 text-sm text-slate-500">The requested page does not exist.</p>
    </section>
  </main>
);

export const AppRoutes = () => (
  <Suspense fallback={<PageLoadingFallback />}>
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route path="/customers/*" element={<CustomerRoutesLazy />} />
          <Route path="/connections/*" element={<ConnectionRoutesLazy />} />
          <Route path="/packages/*" element={<PackageRoutesLazy />} />
          <Route path="/billing/*" element={<BillingRoutesLazy />} />
          <Route path="/payments/*" element={<PaymentRoutesLazy />} />
          <Route path="/finance/*" element={<FinanceRoutesLazy />} />
          <Route path="/network/routers/*" element={<MikrotikRoutesLazy />} />
          <Route path="/network/pppoe/*" element={<PppoeRoutesLazy />} />
          <Route path="/hotspot/*" element={<HotspotRoutesLazy />} />
          <Route path="/support/*" element={<SupportRoutesLazy />} />
          <Route path="/inventory/*" element={<InventoryRoutesLazy />} />
          <Route path="/purchasing/*" element={<PurchasingRoutesLazy />} />
          <Route path="/vendors/*" element={<VendorRoutesLazy />} />
          <Route path="/field-service/*" element={<FieldServiceRoutesLazy />} />
          <Route path="/reports/*" element={<ReportRoutesLazy />} />
          <Route path="/admin/system/*" element={<SystemRoutesLazy />} />
          <Route path="/admin/roles/*" element={<RbacRoutesLazy />} />
          <Route path="/notifications/*" element={<NotificationRoutesLazy />} />
          <Route path="/audit/*" element={<AuditRoutesLazy />} />
          <Route path="/integration/*" element={<IntegrationRoutesLazy />} />
          <Route path="/workflows/*" element={<WorkflowRoutesLazy />} />
        </Route>
      </Route>
      <Route path="/portal/*" element={<PortalRoutesLazy />} />
      <Route path="*" element={<NotFound />} />
    </Routes>
  </Suspense>
);
