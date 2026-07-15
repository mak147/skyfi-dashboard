import { useEffect, useMemo, useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { clsx } from 'clsx';

import { Button } from '@/components/ui/button';
import { useAuth } from '@/hooks/useAuth';

const navigation = [
  { label: 'Dashboard', path: '/portal/dashboard', icon: '⌂' },
  { label: 'My Connection', path: '/portal/connection', icon: '⚡' },
  { label: 'Billing', path: '/portal/billing', icon: '🧾' },
  { label: 'Payments', path: '/portal/payments', icon: '💳' },
  { label: 'Support', path: '/portal/support', icon: '🎧' },
  { label: 'Notifications', path: '/portal/notifications', icon: '🔔' },
  { label: 'Profile', path: '/portal/profile', icon: '👤' },
];

export const PortalLayout = () => {
  const { user, signOut } = useAuth();
  const location = useLocation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [theme, setTheme] = useState<'light' | 'dark'>(() => {
    const stored = localStorage.getItem('skyfi-theme');
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  });

  useEffect(() => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    localStorage.setItem('skyfi-theme', theme);
  }, [theme]);

  const currentPage = useMemo(
    () => navigation.find((item) => location.pathname.startsWith(item.path))?.label ?? 'Portal',
    [location.pathname],
  );

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
      <div
        className={clsx(
          'fixed inset-0 z-30 bg-slate-950/40 transition-opacity lg:hidden',
          isMenuOpen ? 'opacity-100' : 'pointer-events-none opacity-0',
        )}
        aria-hidden="true"
        onClick={() => setIsMenuOpen(false)}
      />

      <aside
        className={clsx(
          'fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col border-r border-slate-200 bg-white shadow-2xl transition duration-200 dark:border-slate-700 dark:bg-slate-900 lg:translate-x-0 lg:shadow-none',
          isMenuOpen ? 'translate-x-0' : '-translate-x-full',
        )}
        aria-label="Customer portal navigation"
      >
        <div className="flex h-16 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-700">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-card">SF</div>
          <div>
            <p className="text-sm font-bold text-slate-900 dark:text-white">SkyFi Networks</p>
            <p className="text-xs text-slate-500">Customer Portal</p>
          </div>
        </div>

        <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
          {navigation.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              onClick={() => setIsMenuOpen(false)}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                  isActive
                    ? 'bg-indigo-50 text-indigo-700 shadow-sm ring-1 ring-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-900'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                )
              }
            >
              <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                {item.icon}
              </span>
              {item.label}
            </NavLink>
          ))}
        </nav>

        <div className="border-t border-slate-200 p-4 dark:border-slate-700">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Signed in</p>
          <p className="mt-2 truncate text-sm font-semibold text-slate-900 dark:text-white">{user?.name}</p>
          <p className="truncate text-xs text-slate-500">{user?.email}</p>
          <Button variant="secondary" size="sm" className="mt-4 w-full" onClick={() => void signOut()}>
            Sign out
          </Button>
        </div>
      </aside>

      <div className="lg:pl-64">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-700 dark:bg-slate-900/90 sm:px-6">
          <div className="flex items-center gap-3">
            <button
              type="button"
              className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 lg:hidden"
              aria-label="Open navigation"
              onClick={() => setIsMenuOpen(true)}
            >
              ☰
            </button>
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Customer Portal</p>
              <p className="text-sm font-semibold text-slate-800 dark:text-slate-100">{currentPage}</p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setTheme((current) => (current === 'dark' ? 'light' : 'dark'))}
              className="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
              aria-label={`Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`}
            >
              {theme === 'dark' ? '☀ Light' : '☾ Dark'}
            </button>
          </div>
        </header>

        <main className="px-4 py-6 sm:px-6 lg:px-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
};
