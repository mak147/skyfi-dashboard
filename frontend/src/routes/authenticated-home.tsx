import { Button } from '@/components/ui/button';
import { useAuth } from '@/hooks/useAuth';

export const AuthenticatedHome = () => {
  const { user, signOut } = useAuth();

  return (
    <main className="min-h-screen bg-slate-50 px-4 py-10 sm:px-6">
      <section className="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-card sm:p-8" aria-labelledby="auth-ready-title">
        <div className="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">SkyFi Networks</p>
            <h1 id="auth-ready-title" className="mt-3 text-2xl font-bold text-slate-800">Authentication is ready</h1>
            <p className="mt-2 max-w-xl text-sm leading-6 text-slate-500">
              You are signed in. Feature modules will be added one at a time without changing the authentication boundary.
            </p>
          </div>
          <Button variant="secondary" onClick={() => void signOut()}>Sign out</Button>
        </div>
        <div className="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Signed-in account</p>
          <p className="mt-2 text-sm font-semibold text-slate-800">{user?.name}</p>
          <p className="text-sm text-slate-500">{user?.email}</p>
          {user?.roles.length ? <p className="mt-3 text-xs text-slate-500">Roles: {user.roles.join(', ')}</p> : null}
        </div>
      </section>
    </main>
  );
};
