import { useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useCreateHotspotProfile } from '../api/useHotspot';
import { HotspotProfileForm } from '../components/HotspotProfileForm';
import type { HotspotProfileFormValues } from '../schemas';

export const CreateProfilePage = () => {
  const navigate = useNavigate();
  const createMutation = useCreateHotspotProfile();

  const onSubmit = (data: HotspotProfileFormValues) => {
    createMutation.mutate(data, {
      onSuccess: () => navigate('/hotspot/profiles'),
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Create Hotspot Profile</h1>
          <p className="mt-1 text-sm text-slate-500">
            Define speed limits, timeouts, and login methods for hotspot users.
          </p>
        </div>
      </div>

      {createMutation.error ? (
        <Alert title="Failed to create profile" variant="danger">
          {apiErrorMessage(createMutation.error)}
        </Alert>
      ) : null}

      <HotspotProfileForm
        onSubmit={onSubmit}
        onCancel={() => navigate('/hotspot/profiles')}
        isSubmitting={createMutation.isPending}
      />
    </div>
  );
};
