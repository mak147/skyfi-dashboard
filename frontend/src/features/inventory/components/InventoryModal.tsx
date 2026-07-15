import type { ReactNode } from 'react';

export const InventoryModal = ({ title, children, onClose, wide = false }: { title: string; children: ReactNode; onClose: () => void; wide?: boolean }) => (
  <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="inventory-modal-title" onMouseDown={(event) => { if (event.currentTarget === event.target) onClose(); }}>
    <section className={`max-h-[92vh] w-full overflow-y-auto rounded-xl bg-white p-6 shadow-2xl ${wide ? 'max-w-5xl' : 'max-w-2xl'}`}>
      <header className="mb-5 flex items-center justify-between"><h2 id="inventory-modal-title" className="text-xl font-semibold text-slate-900">{title}</h2><button type="button" onClick={onClose} className="rounded-md p-2 text-slate-500 hover:bg-slate-100" aria-label="Close dialog">✕</button></header>{children}
    </section>
  </div>
);
