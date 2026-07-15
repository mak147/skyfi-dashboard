export const BarcodePlaceholder = ({ value, kind = 'barcode' }: { value?: string | null; kind?: 'barcode' | 'qr' }) => (
  <div className="inline-flex min-w-36 flex-col items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3" aria-label={`${kind} placeholder for ${value || 'unassigned'}`}>
    {kind === 'qr' ? (
      <div className="grid h-16 w-16 grid-cols-4 gap-0.5 rounded bg-white p-1 ring-1 ring-slate-200" aria-hidden="true">
        {Array.from({ length: 16 }).map((_, index) => <span key={index} className={index % 3 === 0 || index === 5 || index === 14 ? 'bg-slate-800' : 'bg-slate-200'} />)}
      </div>
    ) : (
      <div className="flex h-12 items-stretch gap-0.5 bg-white px-3 py-1 ring-1 ring-slate-200" aria-hidden="true">
        {Array.from({ length: 24 }).map((_, index) => <span key={index} className="bg-slate-800" style={{ width: index % 4 === 0 ? 3 : 1 }} />)}
      </div>
    )}
    <span className="mt-2 max-w-44 truncate font-mono text-xs text-slate-600">{value || `${kind.toUpperCase()} not assigned`}</span>
    {kind === 'qr' && <span className="mt-1 text-[10px] uppercase tracking-wide text-slate-400">QR placeholder</span>}
  </div>
);
