import React from 'react';
import type { VendorPurchasingHistory } from '../types';

interface SupplierStatisticsProps {
  history?: VendorPurchasingHistory;
  isLoading?: boolean;
}

export const SupplierStatistics: React.FC<SupplierStatisticsProps> = ({ history, isLoading }) => {
  if (isLoading || !history) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div className="h-40 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
      </div>
    );
  }

  const { purchase_orders = [], supplier_invoices = [], catalog_products = [], total_procurement_spend = 0 } = history;

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
      {/* Spend Overview Card */}
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 className="text-base font-semibold text-slate-800 dark:text-slate-100">Financial Overview</h3>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Total expenditure across approved POs</p>
        <div className="mt-6 flex items-baseline gap-2">
          <span className="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
            PKR {Number(total_procurement_spend).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
          </span>
        </div>
        <div className="mt-6 space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
          <div className="flex justify-between text-sm">
            <span className="text-slate-500 dark:text-slate-400">Total Purchase Orders:</span>
            <span className="font-semibold text-slate-800 dark:text-slate-200">{purchase_orders.length}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-slate-500 dark:text-slate-400">Registered Supplier Invoices:</span>
            <span className="font-semibold text-slate-800 dark:text-slate-200">{supplier_invoices.length}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-slate-500 dark:text-slate-400">Supplied Catalog SKUs:</span>
            <span className="font-semibold text-slate-800 dark:text-slate-200">{catalog_products.length}</span>
          </div>
        </div>
      </div>

      {/* PO Status Breakdown */}
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 className="text-base font-semibold text-slate-800 dark:text-slate-100">Purchase Orders Summary</h3>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Recent order status breakdown</p>
        <div className="mt-6 space-y-4">
          {['approved', 'sent', 'partially_received', 'fully_received', 'closed', 'draft'].map((status) => {
            const count = purchase_orders.filter((p) => p.status === status).length;
            if (count === 0 && status === 'draft') return null;
            const pct = purchase_orders.length > 0 ? Math.round((count / purchase_orders.length) * 100) : 0;
            return (
              <div key={status} className="space-y-1">
                <div className="flex justify-between text-xs font-medium text-slate-700 capitalize dark:text-slate-300">
                  <span>{status.replaceAll('_', ' ')} ({count})</span>
                  <span>{pct}%</span>
                </div>
                <div className="h-2 w-full rounded-full bg-slate-100 dark:bg-slate-800">
                  <div className="h-2 rounded-full bg-indigo-600 transition-all dark:bg-indigo-500" style={{ width: `${pct}%` }} />
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Supplied SKUs Quick List */}
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 className="text-base font-semibold text-slate-800 dark:text-slate-100">Catalog SKUs Supplied</h3>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Products linked via Inventory Catalog</p>
        <div className="mt-4 max-h-56 space-y-2 overflow-y-auto pr-2">
          {catalog_products.length === 0 ? (
            <div className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">No linked inventory products yet.</div>
          ) : (
            catalog_products.map((prod) => (
              <div key={prod.id} className="flex items-center justify-between rounded-lg bg-slate-50 p-2.5 text-sm dark:bg-slate-800/60">
                <div>
                  <div className="font-medium text-slate-800 dark:text-slate-200">{prod.name}</div>
                  <div className="text-xs text-slate-500 dark:text-slate-400">SKU: {prod.sku} | Lead Time: {prod.lead_time_days} days</div>
                </div>
                <div className="text-right font-semibold text-slate-700 dark:text-slate-300">
                  PKR {Number(prod.last_purchase_cost).toLocaleString()}
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
};
