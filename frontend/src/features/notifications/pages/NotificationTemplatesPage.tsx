import { useState } from 'react';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import {
  useDeleteTemplate,
  useNotificationCatalog,
  useNotificationTemplates,
  useSaveTemplate,
} from '../api/useNotifications';
import { NotificationSkeleton } from '../components/NotificationSkeleton';
import { TemplateEditor } from '../components/TemplateEditor';
import { TemplateTable } from '../components/TemplateTable';
import type { NotificationTemplate } from '../types';

export const NotificationTemplatesPage = () => {
  const { can } = usePermissions();
  const [search, setSearch] = useState('');
  const [channel, setChannel] = useState('');
  const [editing, setEditing] = useState<NotificationTemplate | null | undefined>(undefined);
  const catalog = useNotificationCatalog();
  const list = useNotificationTemplates({ search, channel, per_page: 50 });
  const save = useSaveTemplate(editing?.id);
  const del = useDeleteTemplate();

  if (list.isLoading && !list.data) {
    return <NotificationSkeleton />;
  }

  if (list.error) {
    return <Alert title="Templates unavailable">{apiErrorMessage(list.error)}</Alert>;
  }

  const templates = list.data?.data.map((row) => row.attributes) ?? [];

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Communications</p>
          <h1 className="mt-2 text-2xl font-bold">Notification Templates</h1>
          <p className="mt-1 text-sm text-slate-500">
            Email, SMS, and in-app templates with localization-ready placeholders.
          </p>
        </div>
        {can('notifications.templates') ? (
          <Button onClick={() => setEditing(null)}>New template</Button>
        ) : null}
      </header>

      <section className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 dark:border-slate-700 dark:bg-slate-900">
        <input
          className="h-10 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          placeholder="Search templates…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <select
          className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          value={channel}
          onChange={(e) => setChannel(e.target.value)}
        >
          <option value="">All channels</option>
          {(catalog.data?.channels ?? []).map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
      </section>

      {save.error || del.error ? (
        <Alert title="Template operation failed">{apiErrorMessage(save.error || del.error)}</Alert>
      ) : null}

      {editing !== undefined ? (
        <TemplateEditor
          initial={editing}
          categories={catalog.data?.categories ?? ['system']}
          channels={catalog.data?.channels ?? ['in_app', 'email', 'sms', 'push', 'webhook']}
          isSaving={save.isPending}
          onCancel={() => setEditing(undefined)}
          onSubmit={(values) => {
            save.mutate(values, { onSuccess: () => setEditing(undefined) });
          }}
        />
      ) : null}

      <TemplateTable
        templates={templates}
        isLoading={list.isLoading}
        onEdit={can('notifications.templates') ? setEditing : undefined}
        onDelete={
          can('notifications.templates')
            ? (id) => {
                if (confirm('Soft-delete this template?')) del.mutate(id);
              }
            : undefined
        }
      />
    </div>
  );
};
