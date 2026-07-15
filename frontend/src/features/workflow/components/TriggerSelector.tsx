import type { TriggerCatalogItem } from '../types';

export const TriggerSelector = ({
  triggers,
  value,
  onChange,
}: {
  triggers: TriggerCatalogItem[];
  value: string;
  onChange: (eventKey: string, sourceModule?: string) => void;
}) => (
  <div className="space-y-2">
    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Trigger Event</label>
    <select
      className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900"
      value={value}
      onChange={(e) => {
        const selected = triggers.find((t) => t.event_key === e.target.value);
        onChange(e.target.value, selected?.source_module);
      }}
    >
      <option value="">Select a domain event…</option>
      {triggers.map((trigger) => (
        <option key={trigger.event_key} value={trigger.event_key}>
          {trigger.event_key} ({trigger.source_module})
        </option>
      ))}
    </select>
    {value ? (
      <p className="text-xs text-slate-500">
        {triggers.find((t) => t.event_key === value)?.description || 'Event-based workflow trigger.'}
      </p>
    ) : null}
  </div>
);
