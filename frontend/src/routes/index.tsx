import { Route, Routes } from 'react-router-dom';

import { AuthenticationRoutes } from '@/features/authentication/routes';
import { AuthenticatedHome } from '@/routes/authenticated-home';
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
      <Route path="/" element={<AuthenticatedHome />} />
    </Route>
    <Route path="*" element={<NotFound />} />
  </Routes>
);
