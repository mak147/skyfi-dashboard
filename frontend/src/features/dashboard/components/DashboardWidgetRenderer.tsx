import { clsx } from 'clsx';

import type {
  ChartDashboardWidget,
  DashboardAccent,
  DashboardTrend,
  DashboardWidget,
  GaugeDashboardWidget,
  ListDashboardWidget,
  StatDashboardWidget,
} from '../types';

const accentClasses: Record<DashboardAccent, { badge: string; bar: string; dot: string; soft: string; text: string }> = {
  indigo: {
    badge: 'bg-indigo-50 text-indigo-700 ring-indigo-100',
    bar: 'bg-indigo-500',
    dot: 'bg-indigo-500',
    soft: 'bg-indigo-50',
    text: 'text-indigo-700',
  },
  emerald: {
    badge: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    bar: 'bg-emerald-500',
    dot: 'bg-emerald-500',
    soft: 'bg-emerald-50',
    text: 'text-emerald-700',
  },
  amber: {
    badge: 'bg-amber-50 text-amber-700 ring-amber-100',
    bar: 'bg-amber-500',
    dot: 'bg-amber-500',
    soft: 'bg-amber-50',
    text: 'text-amber-700',
  },
  red: {
    badge: 'bg-red-50 text-red-700 ring-red-100',
    bar: 'bg-red-500',
    dot: 'bg-red-500',
    soft: 'bg-red-50',
    text: 'text-red-700',
  },
};

const trendLabels: Record<DashboardTrend, string> = {
  up: 'Increasing',
  down: 'Decreasing',
  neutral: 'Stable',
};

const trendSymbols: Record<DashboardTrend, string> = {
  up: '↗',
  down: '↘',
  neutral: '→',
};

const datasetColors = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-red-500'];

interface DashboardWidgetRendererProps {
  widget: DashboardWidget;
}

export const DashboardWidgetRenderer = ({ widget }: DashboardWidgetRendererProps) => {
  if (widget.type === 'stat') {
    return <StatWidget widget={widget} />;
  }

  if (widget.type === 'chart') {
    return <ChartWidget widget={widget} />;
  }

  if (widget.type === 'list') {
    return <ListWidget widget={widget} />;
  }

  return <GaugeWidget widget={widget} />;
};

const StatWidget = ({ widget }: { widget: StatDashboardWidget }) => {
  const accent = accentClasses[widget.accent];

  return (
    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="text-sm font-semibold text-slate-600">{widget.title}</h3>
          <p className="mt-3 text-3xl font-bold tracking-tight text-slate-950">{widget.value}</p>
        </div>
        <span className={clsx('rounded-full px-2.5 py-1 text-xs font-semibold ring-1', accent.badge)} aria-label={trendLabels[widget.trend]}>
          {trendSymbols[widget.trend]} {widget.change}
        </span>
      </div>
      <p className="mt-4 text-sm leading-6 text-slate-500">{widget.description}</p>
    </article>
  );
};

const ChartWidget = ({ widget }: { widget: ChartDashboardWidget }) => {
  const values = widget.datasets.flatMap((dataset) => dataset.data);
  const max = Math.max(...values, 1);

  return (
    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-card xl:col-span-2">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-900">{widget.title}</h3>
          <p className="mt-1 text-sm text-slate-500">{widget.chartType === 'doughnut' ? 'Distribution snapshot' : 'Trend snapshot'}</p>
        </div>
        <div className="flex flex-wrap gap-3">
          {widget.datasets.map((dataset, index) => (
            <span key={dataset.label} className="inline-flex items-center gap-2 text-xs font-medium text-slate-500">
              <span className={clsx('h-2.5 w-2.5 rounded-full', datasetColors[index % datasetColors.length])} />
              {dataset.label}
            </span>
          ))}
        </div>
      </div>

      <div className="mt-6 space-y-4">
        {widget.labels.map((label, labelIndex) => (
          <div key={label} className="grid grid-cols-[5rem,1fr] items-center gap-3 sm:grid-cols-[7rem,1fr]">
            <span className="truncate text-xs font-medium text-slate-500">{label}</span>
            <div className="flex min-w-0 items-center gap-2">
              {widget.datasets.map((dataset, datasetIndex) => {
                const value = dataset.data[labelIndex] ?? 0;
                const width = `${Math.max((value / max) * 100, 3)}%`;

                return (
                  <div key={`${dataset.label}-${label}`} className="min-w-0 flex-1">
                    <div className="h-3 rounded-full bg-slate-100">
                      <div className={clsx('h-3 rounded-full', datasetColors[datasetIndex % datasetColors.length])} style={{ width }} />
                    </div>
                    <span className="sr-only">
                      {dataset.label}: {value}
                    </span>
                  </div>
                );
              })}
            </div>
          </div>
        ))}
      </div>
    </article>
  );
};

const ListWidget = ({ widget }: { widget: ListDashboardWidget }) => (
  <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
    <h3 className="text-base font-semibold text-slate-900">{widget.title}</h3>
    <div className="mt-5 divide-y divide-slate-100">
      {widget.items.map((item) => (
        <div key={item.id} className="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-slate-800">{item.primaryText}</p>
            <p className="mt-1 text-xs leading-5 text-slate-500">{item.secondaryText}</p>
          </div>
          <span className="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{item.status}</span>
        </div>
      ))}
    </div>
  </article>
);

const GaugeWidget = ({ widget }: { widget: GaugeDashboardWidget }) => {
  const accent = accentClasses[widget.accent];
  const percentage = Math.round((widget.value / widget.max) * 100);
  const clampedPercentage = Math.min(Math.max(percentage, 0), 100);

  return (
    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
      <h3 className="text-base font-semibold text-slate-900">{widget.title}</h3>
      <div className={clsx('mt-5 rounded-2xl p-5', accent.soft)}>
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className={clsx('text-4xl font-bold tracking-tight', accent.text)}>
              {widget.value}
              {widget.unit}
            </p>
            <p className="mt-1 text-sm text-slate-500">Target max: {widget.max}{widget.unit}</p>
          </div>
          <span className={clsx('h-4 w-4 rounded-full', accent.dot)} aria-hidden="true" />
        </div>
        <div className="mt-6 h-3 rounded-full bg-white/80">
          <div className={clsx('h-3 rounded-full', accent.bar)} style={{ width: `${clampedPercentage}%` }} />
        </div>
      </div>
    </article>
  );
};
