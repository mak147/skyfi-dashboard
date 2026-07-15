import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import type { NotificationTemplate } from '../types';

const empty: Partial<NotificationTemplate> = {
  code: '',
  name: '',
  category: 'system',
  channel: 'in_app',
  subject_template: '',
  body_template: '',
  locale: 'en',
  is_transactional: 0,
  is_active: 1,
  variables: [],
};

export const TemplateEditor = ({
  initial,
  categories,
  channels,
  isSaving,
  onSubmit,
  onCancel,
}: {
  initial?: NotificationTemplate | null;
  categories: string[];
  channels: string[];
  isSaving?: boolean;
  onSubmit: (values: Partial<NotificationTemplate>) => void;
  onCancel?: () => void;
}) => {
  const [form, setForm] = useState<Partial<NotificationTemplate>>(initial ?? empty);

  useEffect(() => {
    setForm(initial ?? empty);
  }, [initial]);

  const set = <K extends keyof NotificationTemplate>(key: K, value: NotificationTemplate[K] | string | number) => {
    setForm((current) => ({ ...current, [key]: value }));
  };

  return (
    <form
      className="space-y-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit({
          ...form,
          is_transactional: form.is_transactional ? 1 : 0,
          is_active: form.is_active ? 1 : 0,
          variables:
            typeof form.variables === 'string'
              ? String(form.variables)
                  .split(',')
                  .map((v) => v.trim())
                  .filter(Boolean)
              : form.variables ?? [],
        });
      }}
    >
      <div className="grid gap-3 md:grid-cols-2">
        <Input placeholder="Code (e.g. invoice.generated)" value={form.code ?? ''} onChange={(e) => set('code', e.target.value)} />
        <Input placeholder="Name" value={form.name ?? ''} onChange={(e) => set('name', e.target.value)} />
        <select
          className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          value={form.category ?? 'system'}
          onChange={(e) => set('category', e.target.value)}
        >
          {categories.map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
        <select
          className="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-800"
          value={form.channel ?? 'in_app'}
          onChange={(e) => set('channel', e.target.value as NotificationTemplate['channel'])}
        >
          {channels.map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
        <Input placeholder="Locale" value={form.locale ?? 'en'} onChange={(e) => set('locale', e.target.value)} />
        <Input
          placeholder="Variables (comma separated)"
          value={Array.isArray(form.variables) ? form.variables.join(', ') : ''}
          onChange={(e) => set('variables', e.target.value.split(',').map((v) => v.trim()).filter(Boolean))}
        />
      </div>
      <Input
        placeholder="Subject template"
        value={form.subject_template ?? ''}
        onChange={(e) => set('subject_template', e.target.value)}
      />
      <textarea
        className="min-h-[140px] w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
        placeholder="Body template — use {{variable}} placeholders"
        value={form.body_template ?? ''}
        onChange={(e) => set('body_template', e.target.value)}
      />
      <div className="flex flex-wrap items-center gap-4">
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={Boolean(form.is_transactional)}
            onChange={(e) => set('is_transactional', e.target.checked ? 1 : 0)}
          />
          Transactional (ignore user opt-out)
        </label>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={Boolean(form.is_active)} onChange={(e) => set('is_active', e.target.checked ? 1 : 0)} />
          Active
        </label>
      </div>
      <div className="flex gap-2">
        <Button type="submit" disabled={isSaving}>
          {isSaving ? 'Saving…' : initial ? 'Update template' : 'Create template'}
        </Button>
        {onCancel ? (
          <Button type="button" variant="secondary" onClick={onCancel}>
            Cancel
          </Button>
        ) : null}
      </div>
    </form>
  );
};
