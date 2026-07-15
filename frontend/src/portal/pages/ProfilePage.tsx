import { useQuery } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { getProfile } from '../api/portalApi';
import { CardSkeleton } from '../components/PortalSkeleton';
import { PasswordForm } from '../components/PasswordForm';
import { ProfileForm } from '../components/ProfileForm';

export const ProfilePage = () => {
  const profileQuery = useQuery({
    queryKey: ['portal', 'profile'],
    queryFn: getProfile,
    staleTime: 2 * 60 * 1000,
  });

  if (profileQuery.isLoading) {
    return <CardSkeleton rows={8} />;
  }

  if (profileQuery.error) {
    return (
      <Alert title="Unable to load profile">
        {apiErrorMessage(profileQuery.error, 'Please try again later.')}
      </Alert>
    );
  }

  const customer = profileQuery.data;
  if (!customer) {
    return <Alert title="Profile not found">Your profile could not be loaded.</Alert>;
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Profile</h1>
        <p className="mt-1 text-sm text-slate-500">Update your contact details and password.</p>
      </div>

      <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <h2 className="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Contact information</h2>
        <ProfileForm customer={customer} />
      </section>

      <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-card dark:border-slate-700 dark:bg-slate-900">
        <h2 className="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Change password</h2>
        <PasswordForm />
      </section>
    </div>
  );
};
