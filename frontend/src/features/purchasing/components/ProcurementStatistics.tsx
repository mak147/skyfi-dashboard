import type { ProcurementDashboard } from '../types';

const Metric = ({ label, value, tone }: { label: string; value: number | string; tone: string }) => (
  <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">{label}</p>
    <p className={`mt-2 text-3xl font-bold tabular-nums ${tone}`}>{typeof value === 'number' ? value.toLocaleString() : value}</p>
  </div>
);

export const ProcurementStatistics = ({ data }: { data: ProcurementDashboard }) => (
  <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
    <Metric label="Open Purchase Orders" value={data.open_purchase_orders} tone="text-indigo-600" />
    <Metric label="Pending Approvals" value={data.pending_approvals} tone="text-amber-600" />
    <Metric label="Goods Received Today" value={data.goods_received_today} tone="text-emerald-600" />
    <Metric label="Outstanding Deliveries" value={data.outstanding_deliveries} tone="text-red-600" />
    <Metric label="Monthly Spend" value={`PKR ${(data.procurement_spend_month / 1000).toFixed(1)}K`} tone="text-blue-600" />
  </div>
);
