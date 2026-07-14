import { useRouterProfiles } from '../api/usePppoe';

interface ProfileSelectorProps {
  routerId: number;
  value: string;
  onChange: (profileName: string) => void;
  disabled?: boolean;
  className?: string;
}

export const ProfileSelector = ({ routerId, value, onChange, disabled = false, className = '' }: ProfileSelectorProps) => {
  const { data: profiles = [], isLoading, isError } = useRouterProfiles(routerId);

  return (
    <div>
      <select
        disabled={disabled || isLoading || !routerId}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className={`h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-slate-50 ${className}`}
      >
        <option value="">{routerId ? (isLoading ? 'Loading profiles from router...' : 'Select PPPoE Profile...') : 'Select a router first'}</option>
        {profiles.map((profile) => (
          <option key={profile.id || profile.name} value={profile.name}>
            {profile.name} {profile.rate_limit ? `(${profile.rate_limit})` : ''} {profile.local_address ? `[IP: ${profile.local_address}]` : ''}
          </option>
        ))}
        {value && !profiles.some((p) => p.name === value) ? (
          <option value={value}>{value} (Custom / Configured)</option>
        ) : null}
      </select>
      {isError ? <p className="mt-1 text-xs text-amber-600">Could not fetch live profiles from router. You may type or select a default.</p> : null}
    </div>
  );
};
