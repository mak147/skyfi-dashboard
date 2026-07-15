import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';

import { LoginForm } from '@/features/authentication/components/LoginForm';

export const LoginPage = () => (
  <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
    <motion.section
      animate={{ opacity: 1, y: 0 }}
      className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-card sm:p-8"
      initial={{ opacity: 0, y: 10 }}
      transition={{ duration: 0.3 }}
      aria-labelledby="portal-login-title"
    >
      <div className="mb-8">
        <div className="mb-6 flex items-center gap-3" aria-label="SkyFi Networks">
          <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-bold text-white">S</span>
          <span className="text-lg font-semibold tracking-tight text-slate-900">SkyFi Networks</span>
        </div>
        <h1 id="portal-login-title" className="text-2xl font-bold leading-tight text-slate-800 sm:text-3xl">
          Customer Portal
        </h1>
        <p className="mt-2 text-sm leading-6 text-slate-500">
          Sign in to manage your internet service, billing, and support requests.
        </p>
      </div>
      <LoginForm defaultRedirect="/portal/dashboard" />
      <div className="mt-6 text-center">
        <Link
          to="/portal/forgot-password"
          className="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
        >
          Forgot your password?
        </Link>
      </div>
      <p className="mt-8 text-center text-xs leading-5 text-slate-500">
        Your session is protected with short-lived access tokens and secure refresh-token rotation.
      </p>
    </motion.section>
  </main>
);
