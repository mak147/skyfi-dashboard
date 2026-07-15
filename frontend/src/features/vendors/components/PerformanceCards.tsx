import React from 'react';
import type { VendorPerformanceMetrics } from '../types';

interface PerformanceCardsProps {
  metrics?: VendorPerformanceMetrics;
  isLoading?: boolean;
}

export const PerformanceCards: React.FC<PerformanceCardsProps> = ({ metrics, isLoading }) => {
  if (isLoading || !metrics) {
    return (
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
        {[...Array<number>(6)].map((_, i) => (
          <div key={i} className="h-28 animate-pulse rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" />
        ))}
      </div>
    );
  }

  const cards = [
    {
      title: 'Overall Rating',
      value: `${Number(metrics.overall_rating || 5.0).toFixed(2)} / 5.00`,
      subtitle: 'Weighted Supplier Score',
      color: Number(metrics.overall_rating) >= 4.0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
      icon: '★',
    },
    {
      title: 'Delivery Performance',
      value: `${Number(metrics.delivery_performance || 100).toFixed(1)}%`,
      subtitle: 'On-Time Fulfillment',
      color: Number(metrics.delivery_performance) >= 95 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
      icon: '🚚',
    },
    {
      title: 'Order Completion',
      value: `${Number(metrics.order_completion || 100).toFixed(1)}%`,
      subtitle: `${metrics.completed_orders} of ${metrics.total_orders} POs Closed`,
      color: 'text-indigo-600 dark:text-indigo-400',
      icon: '📦',
    },
    {
      title: 'Product Quality',
      value: `${Number(metrics.product_quality || 100).toFixed(1)}%`,
      subtitle: 'Accepted Goods Rate',
      color: Number(metrics.product_quality) >= 98 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
      icon: '✓',
    },
    {
      title: 'Defect / Return Rate',
      value: `${Number(metrics.return_rate || 0).toFixed(1)}%`,
      subtitle: 'Damaged or Returned Goods',
      color: Number(metrics.return_rate) <= 2 ? 'text-slate-700 dark:text-slate-300' : 'text-red-600 dark:text-red-400',
      icon: '⚠️',
    },
    {
      title: 'Average Lead Time',
      value: `${Number(metrics.average_lead_time_days || 7).toFixed(0)} Days`,
      subtitle: 'Catalog Fulfillment Speed',
      color: 'text-blue-600 dark:text-blue-400',
      icon: '⏱️',
    },
  ];

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
      {cards.map((card, idx) => (
        <div
          key={idx}
          className="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-slate-500 uppercase tracking-wider dark:text-slate-400">{card.title}</span>
            <span className="text-lg">{card.icon}</span>
          </div>
          <div className="mt-2">
            <div className={`text-2xl font-bold ${card.color}`}>{card.value}</div>
            <div className="mt-1 text-xs text-slate-400 dark:text-slate-500">{card.subtitle}</div>
          </div>
        </div>
      ))}
    </div>
  );
};
