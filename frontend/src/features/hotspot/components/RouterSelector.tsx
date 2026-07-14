interface RouterSelectorProps {
  value: string;
  onChange: (value: string) => void;
  label?: string;
  placeholder?: string;
  disabled?: boolean;
}

export const RouterSelector = ({
  value,
  onChange,
  label = 'Router ID',
  placeholder = 'Enter or select a MikroTik router ID...',
  disabled = false,
}: RouterSelectorProps) => {
  return (
    <div>
      <label className="block text-sm font-semibold text-slate-700">{label}</label>
      <input
        type="number"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        disabled={disabled}
        className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-400"
      />
      <p className="mt-1 text-xs text-slate-400">
        Select a MikroTik router to manage hotspot configuration.
      </p>
    </div>
  );
};
