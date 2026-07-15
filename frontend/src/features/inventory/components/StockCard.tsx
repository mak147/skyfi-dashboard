import type { StockBalance } from '../types';

export const StockCard = ({ stock }: { stock: StockBalance }) => (
  <article className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div className="flex items-start justify-between gap-3">
      <div>
        <p className="font-mono text-xs font-semibold text-indigo-600">{stock.sku}</p>
        <h3 className="mt-1 font-semibold text-slate-900">{stock.product_name}</h3>
        <p className="mt-1 text-xs text-slate-500">{stock.warehouse_name} · {stock.location_code}</p>
      </div>
      <span className={`rounded-full px-2 py-1 text-xs font-semibold capitalize ${stock.is_low_stock ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`}>{stock.stock_condition}</span>
    </div>
    <div className="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3">
      <div><p className="text-xs text-slate-400">Quantity</p><p className="font-semibold tabular-nums">{Number(stock.quantity).toLocaleString()} {stock.unit_symbol}</p></div>
      <div><p className="text-xs text-slate-400">Value</p><p className="font-semibold tabular-nums">PKR {Number(stock.stock_value).toLocaleString()}</p></div>
    </div>
  </article>
);
