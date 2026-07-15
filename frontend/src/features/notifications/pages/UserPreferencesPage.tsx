import { useEffect, useState } from 'react';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { useNotificationPreferences, useUpdatePreferences } from '../api/useNotifications';
import { NotificationSkeleton } from '../components/NotificationSkeleton';
import { PreferenceForm } from '../components/PreferenceForm';
import type { UserPreferenceRow } from '../types';

export const UserPreferencesPage = () => {
  const query = useNotificationPreferences();
  const save = useUpdatePreferences();
  const [rows, setRows] = useState<UserPreferenceRow[]>([]);

  useEffect(() => {
    if (query.data?.preferences) {
      setRows(query.data.preferences);
    }
  }, [query.data]);

  if (query.isLoading && !query.data) {
    return <NotificationSkeleton />;
  }

  if (query.error || !query.data) {
    return <Alert title="Preferences unavailable">{apiErrorMessage(query.error)}</Alert>;
  }

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Communications</p>
        <h1 className="mt-2 text-2xl font-bold">Notification Preferences</h1>
        <p className="mt-1 text-sm text-slate-500">
          Enable or disable channels and configure quiet hours for non-transactional notifications.
        </p>
      </header>

      {save.error ? <Alert title="Unable to save preferences">{apiErrorMessage(save.error)}</Alert> : null}
      {save.isSuccess ? (
        <Alert title="Preferences saved" variant="success">
          Your delivery preferences were updated.
        </Alert>
      ) : null}

      <PreferenceForm value={rows} categories={query.data.categories} onChange={setRows} />

      <Button disabled={save.isPending} onClick={() => save.mutate(rows)}>
        {save.isPending ? 'Saving…' : 'Save preferences'}
      </Button>
    </div>
  );
};
