import { useParams, useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useHotspotProfile, useUpdateHotspotProfile } from '../api/useHotspot';
import { HotspotProfileForm } from '../components/HotspotProfileForm';
import type { HotspotProfileFormValues } from '../schemas';

export const EditProfilePage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const profileId = Number(id ?? '0');

  const { data: profile, isLoading } = useHotspotProfile(profileId);
  const updateMutation = useUpdateHotspotProfile();

  const onSubmit = (data: HotspotProfileFormValues) => {
    updateMutation.mutate(
      { id: profileId, data },
      { onSuccess: () => navigate('/hotspot/profiles') },
    );
  };

  if (isLoading) {
    return <div className="h-64 animate-pulse rounded-xl bg-slate-100" />;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Edit Hotspot Profile</h1>
          <p className="mt-1 text-sm text-slate-500">Update {profile?.name}.</p>
        </div>
      </div>

      {updateMutation.error ? (
        <Alert title="Failed to update profile" variant="danger">
          {apiErrorMessage(updateMutation.error)}
        </Alert>
      ) : null}

      <HotspotProfileForm
        profile={profile}
        onSubmit={onSubmit}
        onCancel={() => navigate('/hotspot/profiles')}
        isSubmitting={updateMutation.isPending}
      />
    </div>
  );
};
