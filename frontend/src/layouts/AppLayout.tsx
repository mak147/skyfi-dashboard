import { useEffect, useMemo, useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import { clsx } from 'clsx';

import { Button } from '@/components/ui/button';
import { canViewNavigationItem, navigationGroups } from '@/config/navigation';
import { NotificationBell } from '@/features/notifications/components/NotificationBell';
import { useAuth } from '@/hooks/useAuth';
import { usePermissions } from '@/hooks/usePermissions';

export const AppLayout = () => {
  const { user, signOut } = useAuth();
  const { data: permissions = [] } = usePermissions();
  const [isSidebarOpen, setSidebarOpen] = useState(false);
  const [theme, setTheme] = useState<'light' | 'dark'>(() => {
    const stored = localStorage.getItem('skyfi-theme');
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  });
  useEffect(() => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    localStorage.setItem('skyfi-theme', theme);
  }, [theme]);
  const visibleNavigation = useMemo(
    () =>
      navigationGroups
        .map((group) => ({
          ...group,
          items: group.items.filter((item) => canViewNavigationItem(item, user, permissions)),
        }))
        .filter((group) => group.items.length > 0),
    [permissions, user],
  );

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
      <div
        className={clsx(
          'fixed inset-0 z-30 bg-slate-950/40 transition-opacity lg:hidden',
          isSidebarOpen ? 'opacity-100' : 'pointer-events-none opacity-0',
        )}
        aria-hidden="true"
        onClick={() => setSidebarOpen(false)}
      />

      <aside
        className={clsx(
          'fixed inset-y-0 left-0 z-40 flex w-72 transform flex-col border-r border-slate-200 bg-white shadow-2xl transition duration-200 dark:border-slate-700 dark:bg-slate-900 lg:translate-x-0 lg:shadow-none',
          isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
        )}
        aria-label="Primary navigation"
      >
        <div className="flex h-16 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-700">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-card">SF</div>
          <div>
            <p className="text-sm font-bold text-slate-900 dark:text-white">SkyFi Networks</p>
            <p className="text-xs text-slate-500">ISP Management</p>
          </div>
        </div>

        <nav className="flex-1 space-y-6 overflow-y-auto px-4 py-6">
          {visibleNavigation.map((group) => (
            <section key={group.label} aria-labelledby={`nav-${group.label.toLowerCase().replaceAll(' ', '-')}`}>
              <h2 id={`nav-${group.label.toLowerCase().replaceAll(' ', '-')}`} className="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                {group.label}
              </h2>
              <div className="mt-2 space-y-1">
                {group.items.map((item) => (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    onClick={() => setSidebarOpen(false)}
                    className={({ isActive }) =>
                      clsx(
                        'group flex items-start gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition',
                        isActive
                          ? 'bg-indigo-50 text-indigo-700 shadow-sm ring-1 ring-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-900'
                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                      )
                    }
                  >
                    <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500 group-hover:bg-white dark:bg-slate-800 dark:text-slate-300 dark:group-hover:bg-slate-700">
                      {item.icon}
                    </span>
                    <span>
                      <span className="block">{item.label}</span>
                      <span className="mt-0.5 block text-xs font-normal leading-5 text-slate-400">{item.description}</span>
                    </span>
                  </NavLink>
                ))}
              </div>
            </section>
          ))}
        </nav>

        <div className="border-t border-slate-200 p-4 dark:border-slate-700">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Signed in</p>
          <p className="mt-2 truncate text-sm font-semibold text-slate-900 dark:text-white">{user?.name}</p>
          <p className="truncate text-xs text-slate-500">{user?.email}</p>
          {user?.roles.length ? <p className="mt-2 line-clamp-2 text-xs text-slate-500">{user.roles.join(', ')}</p> : null}
        </div>
      </aside>

      <div className="lg:pl-72">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-700 dark:bg-slate-900/90 sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            <button
              type="button"
              className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 lg:hidden"
              aria-label="Open navigation"
              onClick={() => setSidebarOpen(true)}
            >
              ☰
            </button>
            <div className="hidden min-w-0 sm:block">
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Staff Portal</p>
              <p className="truncate text-sm text-slate-500">Notification center is live; global search remains reserved for a future module.</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setTheme((current) => current === 'dark' ? 'light' : 'dark')}
              className="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
              aria-label={`Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`}
              title={`Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`}
            >
              {theme === 'dark' ? '☀ Light' : '☾ Dark'}
            </button>
            <NotificationBell />
            <button
              type="button"
              className="hidden rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-400 sm:inline-flex"
              disabled
              title="Global search will be connected when searchable modules are implemented."
            >
              Search disabled
            </button>
            <Button variant="secondary" size="sm" onClick={() => void signOut()}>
              Sign out
            </Button>
          </div>
        </header>

        <main className="px-4 py-6 sm:px-6 lg:px-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
};
