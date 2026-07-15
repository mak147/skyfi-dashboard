import type { EventRegistryItem, PageMeta } from '../types';

interface Props {
  items: EventRegistryItem[];
  meta?: PageMeta;
  onPageChange?: (page: number) => void;
  selectedModule?: string | null;
  modules?: string[];
  onModuleChange?: (mod: string | null) => void;
}

const moduleColor: Record<string, string> = {
  customers: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
  billing: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
  payments: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
  finance: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
  inventory: 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300',
  purchasing: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300',
  vendors: 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-300',
  support: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300',
  monitoring: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
  pppoe: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
  hotspot: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',
  'field-service': 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300',
  notifications: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
};

export const EventTimeline = ({ items, meta, onPageChange, selectedModule, modules = [], onModuleChange }: Props) => (
  <div className="space-y-4">
    {modules.length > 0 && (
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => onModuleChange?.(null)}
          className={`rounded-full px-3 py-1 text-xs font-medium transition ${selectedModule === null ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'}`}
        >
          All
        </button>
        {modules.map((m) => (
          <button
            key={m}
            type="button"
            onClick={() => onModuleChange?.(m)}
            className={`rounded-full px-3 py-1 text-xs font-medium transition ${selectedModule === m ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'}`}
          >
            {m}
          </button>
        ))}
      </div>
    )}

    <div className="space-y-2">
      {items.map((ev) => (
        <div key={ev.id} className="flex items-start gap-3 rounded-lg border border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
          <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
            {ev.source_module.slice(0, 2).toUpperCase()}
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex items-center gap-2">
              <code className="text-sm font-semibold text-slate-900 dark:text-white">{ev.event_key}</code>
              <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${moduleColor[ev.source_module] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'}`}>
                {ev.source_module}
              </span>
            </div>
            {ev.description && <p className="mt-0.5 text-xs text-slate-500">{ev.description}</p>}
          </div>
          <span className={`shrink-0 text-xs ${ev.is_active ? 'text-green-600' : 'text-slate-400'}`}>
            {ev.is_active ? '● Active' : '○ Inactive'}
          </span>
        </div>
      ))}
      {items.length === 0 && <p className="py-8 text-center text-sm text-slate-400">No events found.</p>}
    </div>

    {meta && meta.last_page > 1 && (
      <div className="flex items-center justify-between text-sm">
        <span className="text-slate-500">{meta.total} events</span>
        <div className="flex gap-2">
          <button type="button" className="rounded-lg border border-slate-200 px-3 py-1 text-xs disabled:opacity-50 dark:border-slate-700" disabled={meta.current_page <= 1} onClick={() => onPageChange?.(meta.current_page - 1)}>Prev</button>
          <button type="button" className="rounded-lg border border-slate-200 px-3 py-1 text-xs disabled:opacity-50 dark:border-slate-700" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange?.(meta.current_page + 1)}>Next</button>
        </div>
      </div>
    )}
  </div>
);
