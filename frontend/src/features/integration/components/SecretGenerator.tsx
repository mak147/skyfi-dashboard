export const SecretGenerator = ({ secret, onDismiss }: { secret: string; onDismiss: () => void }) => (
  <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950">
    <p className="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
      New secret generated — copy it now
    </p>
    <code className="mt-2 block break-all rounded bg-white p-3 text-sm text-slate-800 dark:bg-slate-900 dark:text-slate-200">
      {secret}
    </code>
    <button
      type="button"
      className="mt-3 rounded-lg border border-amber-300 px-4 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-600 dark:text-amber-200 dark:hover:bg-amber-900"
      onClick={onDismiss}
    >
      Dismiss
    </button>
  </div>
);
