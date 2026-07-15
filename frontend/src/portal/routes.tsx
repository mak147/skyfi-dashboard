import type { ReactNode } from 'react';
import { Navigate, Route, Routes, useLocation } from 'react-router-dom';

import { useAuth } from '@/hooks/useAuth';

import { PortalLayout } from './layouts/PortalLayout';
import { BillingPage } from './pages/BillingPage';
import { CreateTicketPage } from './pages/CreateTicketPage';
import { DashboardPage } from './pages/DashboardPage';
import { ForgotPasswordPage } from './pages/ForgotPasswordPage';
import { InvoiceDetailPage } from './pages/InvoiceDetailPage';
import { LoginPage } from './pages/LoginPage';
import { MyConnectionPage } from './pages/MyConnectionPage';
import { NotificationsPage } from './pages/NotificationsPage';
import { PaymentDetailPage } from './pages/PaymentDetailPage';
import { PaymentsPage } from './pages/PaymentsPage';
import { ProfilePage } from './pages/ProfilePage';
import { ResetPasswordPage } from './pages/ResetPasswordPage';
import { SupportPage } from './pages/SupportPage';
import { TicketDetailPage } from './pages/TicketDetailPage';

const CUSTOMER_ROLE = 'Customer';

const PortalAuthGuard = ({ children }: { children: ReactNode }) => {
  const { isAuthenticated, isInitialized, user } = useAuth();
  const location = useLocation();

  if (!isInitialized) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-50" aria-live="polite">
        <div
          className="h-8 w-8 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent"
          aria-label="Loading"
        />
      </main>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/portal/login" replace state={{ from: location }} />;
  }

  if (!user?.roles.includes(CUSTOMER_ROLE)) {
    return <Navigate to="/" replace />;
  }

  return children;
};

const PublicPortalRoute = ({ children }: { children: ReactNode }) => {
  const { isAuthenticated, user } = useAuth();

  if (isAuthenticated && user?.roles.includes(CUSTOMER_ROLE)) {
    return <Navigate to="/portal/dashboard" replace />;
  }

  return children;
};

export const PortalRoutes = () => (
  <Routes>
    <Route
      path="login"
      element={
        <PublicPortalRoute>
          <LoginPage />
        </PublicPortalRoute>
      }
    />
    <Route
      path="forgot-password"
      element={
        <PublicPortalRoute>
          <ForgotPasswordPage />
        </PublicPortalRoute>
      }
    />
    <Route
      path="reset-password"
      element={
        <PublicPortalRoute>
          <ResetPasswordPage />
        </PublicPortalRoute>
      }
    />
    <Route
      element={
        <PortalAuthGuard>
          <PortalLayout />
        </PortalAuthGuard>
      }
    >
      <Route index element={<Navigate to="/portal/dashboard" replace />} />
      <Route path="dashboard" element={<DashboardPage />} />
      <Route path="connection" element={<MyConnectionPage />} />
      <Route path="billing" element={<BillingPage />} />
      <Route path="billing/:id" element={<InvoiceDetailPage />} />
      <Route path="payments" element={<PaymentsPage />} />
      <Route path="payments/:id" element={<PaymentDetailPage />} />
      <Route path="support" element={<SupportPage />} />
      <Route path="support/new" element={<CreateTicketPage />} />
      <Route path="support/:id" element={<TicketDetailPage />} />
      <Route path="notifications" element={<NotificationsPage />} />
      <Route path="profile" element={<ProfilePage />} />
    </Route>
    <Route path="*" element={<Navigate to="/portal/login" replace />} />
  </Routes>
);
