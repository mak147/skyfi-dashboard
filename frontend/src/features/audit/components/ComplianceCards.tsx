import type { CompliancePolicy } from '../types';

const policyTypeIcons: Record<string, string> = {
  data_retention: '🗄',
  access_control: '🔐',
  immutability: '🔒',
  privacy: '🛡',
  custom: '⚙',
};

const policyTypeColors: Record<string, string> = {
  data_retention: 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20',
  access_control: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20',
  immutability: 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20',
  privacy: 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20',
  custom: 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900',
};

interface ComplianceCardsProps {
  policies: CompliancePolicy[];
  onEdit?: (policy: CompliancePolicy) => void;
  onDelete?: (id: number) => void;
}

export const ComplianceCards = ({ policies, onEdit, onDelete }: ComplianceCardsProps) => {
  if (policies.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">
        No compliance policies configured.
      </div>
    );
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {policies.map((policy) => {
        const icon = policyTypeIcons[policy.policy_type] ?? '⚙';
        const colorClass = policyTypeColors[policy.policy_type] ?? policyTypeColors.custom;
        const isActive = Boolean(policy.is_active);

        return (
          <div key={policy.id} className={`rounded-xl border p-5 ${colorClass} ${!isActive ? 'opacity-60' : ''}`}>
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                <span className="text-xl">{icon}</span>
                <div>
                  <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100">{policy.name}</h3>
                  <p className="text-xs text-slate-500 dark:text-slate-400">{policy.policy_type.replace('_', ' ')}</p>
                </div>
              </div>
              <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'}`}>
                {isActive ? 'Active' : 'Inactive'}
              </span>
            </div>

            {policy.description && (
              <p className="mt-3 text-xs text-slate-600 dark:text-slate-300">{policy.description}</p>
            )}

            {policy.rules && Object.keys(policy.rules).length > 0 && (
              <div className="mt-3 rounded-lg bg-white/60 p-2 dark:bg-black/20">
                <p className="mb-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Rules</p>
                {Object.entries(policy.rules).map(([key, value]) => (
                  <div key={key} className="flex justify-between text-xs">
                    <span className="font-mono text-slate-600 dark:text-slate-300">{key}</span>
                    <span className="text-slate-500 dark:text-slate-400">{String(value)}</span>
                  </div>
                ))}
              </div>
            )}

            {(onEdit || onDelete) && (
              <div className="mt-4 flex gap-2">
                {onEdit && (
                  <button
                    type="button"
                    onClick={() => onEdit(policy)}
                    className="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                  >
                    Edit
                  </button>
                )}
                {onDelete && (
                  <button
                    type="button"
                    onClick={() => onDelete(policy.id)}
                    className="rounded-lg border border-red-200 bg-white px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-800 dark:bg-slate-800 dark:text-red-400 dark:hover:bg-slate-700"
                  >
                    Deactivate
                  </button>
                )}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
};
