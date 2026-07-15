import type { UserPreferenceRow } from '../types';

const CHANNELS = ['in_app', 'email', 'sms', 'push', 'webhook'] as const;

export const PreferenceForm = ({
  value,
  categories,
  onChange,
}: {
  value: UserPreferenceRow[];
  categories: string[];
  onChange: (next: UserPreferenceRow[]) => void;
}) => {
  const rows = CHANNELS.map((channel) => {
    const existing = value.find((row) => row.channel === channel && (row.category === '*' || !row.category));
    return (
      existing ?? {
        channel,
        category: '*',
        is_enabled: channel === 'in_app' || channel === 'email',
        quiet_hours_start: null,
        quiet_hours_end: null,
        quiet_hours_timezone: 'Asia/Karachi',
      }
    );
  });

  const update = (channel: string, patch: Partial<UserPreferenceRow>) => {
    const next = rows.map((row) => (row.channel === channel ? { ...row, ...patch, category: '*' } : row));
    // preserve any category-specific rows not shown in this simplified form
    const extras = value.filter((row) => row.category && row.category !== '*');
    onChange([...next, ...extras]);
  };

  return (
    <div className="space-y-4">
      <p className="text-sm text-slate-500">
        Control delivery channels and quiet hours. Transactional network alerts ignore opt-outs. Categories available:{' '}
        {categories.join(', ') || 'all'}.
      </p>
      <div className="grid gap-3">
        {rows.map((row) => (
          <div
            key={row.channel}
            className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[140px_1fr] dark:border-slate-700 dark:bg-slate-900"
          >
            <label className="flex items-center gap-3 text-sm font-semibold capitalize text-slate-800 dark:text-slate-100">
              <input
                type="checkbox"
                checked={Boolean(row.is_enabled)}
                onChange={(e) => update(row.channel, { is_enabled: e.target.checked ? 1 : 0 })}
              />
              {row.channel.replace('_', ' ')}
            </label>
            <div className="grid gap-2 sm:grid-cols-3">
              <input
                type="time"
                className="h-10 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
                value={(row.quiet_hours_start ?? '').toString().slice(0, 5)}
                onChange={(e) => update(row.channel, { quiet_hours_start: e.target.value || null })}
                aria-label={`${row.channel} quiet hours start`}
              />
              <input
                type="time"
                className="h-10 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
                value={(row.quiet_hours_end ?? '').toString().slice(0, 5)}
                onChange={(e) => update(row.channel, { quiet_hours_end: e.target.value || null })}
                aria-label={`${row.channel} quiet hours end`}
              />
              <input
                className="h-10 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
                value={row.quiet_hours_timezone ?? 'Asia/Karachi'}
                onChange={(e) => update(row.channel, { quiet_hours_timezone: e.target.value })}
                placeholder="Timezone"
                aria-label={`${row.channel} timezone`}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
