import type { WorkflowDefinition } from '../types';
import { WorkflowStatusBadge } from './WorkflowStatusBadge';

export const WorkflowCanvas = ({
  definition,
  scheduleMode,
}: {
  definition: WorkflowDefinition;
  scheduleMode?: string;
}) => {
  const conditions = definition.conditions;
  const conditionCount = conditions?.rules?.length ?? (conditions?.field ? 1 : 0);

  return (
    <div className="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-5 dark:border-slate-700 dark:from-slate-900 dark:to-slate-950">
      <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-100">Visual Flow</h3>
      <div className="mt-4 flex flex-col gap-3 md:flex-row md:items-stretch">
        <CanvasNode
          title="Trigger"
          subtitle={definition.trigger?.event_key || 'Not selected'}
          tone="indigo"
          meta={definition.trigger?.source_module || 'event'}
        />
        <Connector />
        <CanvasNode
          title="Conditions"
          subtitle={`${conditionCount} rule(s)`}
          tone="amber"
          meta={(conditions?.logic as string) || 'AND'}
        />
        <Connector />
        <CanvasNode
          title="Schedule"
          subtitle={scheduleMode || definition.schedule?.mode || 'immediate'}
          tone="sky"
          meta={definition.schedule?.cron || `${definition.schedule?.delay_seconds ?? 0}s delay`}
        />
        <Connector />
        <div className="flex-1 space-y-2">
          {(definition.actions ?? []).map((action, index) => (
            <div
              key={`${action.type}-${index}`}
              className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 dark:border-emerald-900 dark:bg-emerald-950/30"
            >
              <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                  {index + 1}. {action.name || action.type}
                </p>
                <WorkflowStatusBadge status={action.is_enabled === false ? 'disabled' : 'active'} />
              </div>
              <p className="mt-1 text-xs text-emerald-800/80 dark:text-emerald-200/80">{action.type}</p>
            </div>
          ))}
          {(definition.actions ?? []).length === 0 ? (
            <div className="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">
              No actions configured.
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
};

const CanvasNode = ({
  title,
  subtitle,
  meta,
  tone,
}: {
  title: string;
  subtitle: string;
  meta: string;
  tone: 'indigo' | 'amber' | 'sky';
}) => {
  const tones = {
    indigo: 'border-indigo-200 bg-indigo-50 dark:border-indigo-900 dark:bg-indigo-950/30',
    amber: 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30',
    sky: 'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/30',
  };

  return (
    <div className={`min-w-[140px] flex-1 rounded-lg border px-3 py-3 ${tones[tone]}`}>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p>
      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-white break-all">{subtitle}</p>
      <p className="mt-1 text-xs text-slate-500">{meta}</p>
    </div>
  );
};

const Connector = () => (
  <div className="hidden items-center md:flex">
    <div className="h-px w-6 bg-slate-300 dark:bg-slate-600" />
    <div className="h-2 w-2 rotate-45 border-r border-t border-slate-300 dark:border-slate-600" />
  </div>
);
