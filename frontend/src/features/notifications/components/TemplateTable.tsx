import { Button } from '@/components/ui/button';

import type { NotificationTemplate } from '../types';

export const TemplateTable = ({
  templates,
  isLoading,
  onEdit,
  onDelete,
}: {
  templates: NotificationTemplate[];
  isLoading?: boolean;
  onEdit?: (template: NotificationTemplate) => void;
  onDelete?: (id: number) => void;
}) => {
  if (isLoading) {
    return <div className="h-64 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800" />;
  }

  if (templates.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-slate-700">
        No templates found.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
      <table className="min-w-full text-left text-sm">
        <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-800">
          <tr>
            <th className="px-4 py-3">Code</th>
            <th className="px-4 py-3">Name</th>
            <th className="px-4 py-3">Category</th>
            <th className="px-4 py-3">Channel</th>
            <th className="px-4 py-3">Locale</th>
            <th className="px-4 py-3">Flags</th>
            <th className="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          {templates.map((t) => (
            <tr key={t.id} className="border-b border-slate-100 dark:border-slate-800">
              <td className="px-4 py-3 font-mono text-xs">{t.code}</td>
              <td className="px-4 py-3 font-semibold">{t.name}</td>
              <td className="px-4 py-3 capitalize">{t.category}</td>
              <td className="px-4 py-3">{t.channel}</td>
              <td className="px-4 py-3">{t.locale}</td>
              <td className="px-4 py-3 text-xs text-slate-500">
                {t.is_active ? 'active' : 'inactive'}
                {t.is_transactional ? ' · transactional' : ''}
              </td>
              <td className="px-4 py-3">
                <div className="flex gap-2">
                  {onEdit ? (
                    <Button size="sm" variant="secondary" onClick={() => onEdit(t)}>
                      Edit
                    </Button>
                  ) : null}
                  {onDelete ? (
                    <Button size="sm" variant="secondary" onClick={() => onDelete(t.id)}>
                      Delete
                    </Button>
                  ) : null}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
