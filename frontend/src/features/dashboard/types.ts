export type DashboardTrend = 'up' | 'down' | 'neutral';
export type DashboardAccent = 'indigo' | 'emerald' | 'amber' | 'red';

interface DashboardWidgetBase {
  id: string;
  title: string;
  type: 'stat' | 'chart' | 'list' | 'gauge';
}

export interface StatDashboardWidget extends DashboardWidgetBase {
  type: 'stat';
  value: string;
  change: string;
  trend: DashboardTrend;
  accent: DashboardAccent;
  description: string;
}

export interface ChartDashboardWidget extends DashboardWidgetBase {
  type: 'chart';
  chartType: 'line' | 'bar' | 'doughnut';
  labels: string[];
  datasets: Array<{
    label: string;
    data: number[];
  }>;
}

export interface ListDashboardWidget extends DashboardWidgetBase {
  type: 'list';
  items: Array<{
    id: string;
    primaryText: string;
    secondaryText: string;
    status: string;
  }>;
}

export interface GaugeDashboardWidget extends DashboardWidgetBase {
  type: 'gauge';
  value: number;
  max: number;
  unit: string;
  accent: DashboardAccent;
}

export type DashboardWidget = StatDashboardWidget | ChartDashboardWidget | ListDashboardWidget | GaugeDashboardWidget;

export interface DashboardPayload {
  generatedAt: string;
  cacheTtlSeconds: number;
  scope: {
    key: string;
    title: string;
    description: string;
  };
  roles: string[];
  widgets: DashboardWidget[];
}
