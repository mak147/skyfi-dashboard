import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';

import { useCreateWorkflow, useUpdateWorkflow, useWorkflow, useWorkflowCatalog } from '../api/useWorkflow';
import { ActionBuilder } from '../components/ActionBuilder';
import { ConditionBuilder } from '../components/ConditionBuilder';
import { TriggerSelector } from '../components/TriggerSelector';
import { WorkflowCanvas } from '../components/WorkflowCanvas';
import { WorkflowSkeleton } from '../components/WorkflowSkeleton';
import type { ConditionRule, WorkflowDefinition, WorkflowFormValues, WorkflowStatus, ScheduleMode } from '../types';

const defaultDefinition = (): WorkflowDefinition => ({
  trigger: { event_key: '', source_module: 'system' },
  conditions: { logic: 'AND', rules: [] },
  actions: [],
  schedule: { mode: 'immediate', delay_seconds: 0, cron: null },
});

export const WorkflowBuilderPage = () => {
  const { id } = useParams();
  const workflowId = id ? Number(id) : 0;
  const isEdit = workflowId > 0;
  const navigate = useNavigate();
  const catalog = useWorkflowCatalog();
  const detail = useWorkflow(workflowId);
  const create = useCreateWorkflow();
  const update = useUpdateWorkflow();

  const [form, setForm] = useState<WorkflowFormValues>({
    name: '',
    description: '',
    status: 'draft',
    is_enabled: false,
    schedule_mode: 'immediate',
    cron_expression: '',
    delay_seconds: 0,
    max_retries: 0,
    retry_delay_seconds: 60,
    definition: defaultDefinition(),
    changelog: '',
  });
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!detail.data) return;
    const d = detail.data;
    const definition = d.definition?.trigger ? d.definition : defaultDefinition();
    setForm({
      name: d.workflow.name,
      description: d.workflow.description ?? '',
      status: d.workflow.status,
      is_enabled: d.workflow.is_enabled,
      schedule_mode: d.workflow.schedule_mode,
      cron_expression: d.workflow.cron_expression ?? '',
      delay_seconds: d.workflow.delay_seconds,
      max_retries: d.workflow.max_retries,
      retry_delay_seconds: d.workflow.retry_delay_seconds,
      definition,
      changelog: '',
    });
  }, [detail.data]);

  const triggers = useMemo(() => catalog.data?.triggers ?? [], [catalog.data]);
  const actions = useMemo(() => catalog.data?.actions ?? [], [catalog.data]);
  const operators = useMemo(() => catalog.data?.operators ?? [], [catalog.data]);

  const saving = create.isPending || update.isPending;

  const save = async () => {
    setError(null);
    if (!form.name.trim()) {
      setError('Workflow name is required.');
      return;
    }
    if (!form.definition.trigger.event_key) {
      setError('A trigger event is required.');
      return;
    }
    if (!form.definition.actions.length) {
      setError('At least one action is required.');
      return;
    }

    const payload = {
      ...form,
      definition: {
        ...form.definition,
        schedule: {
          mode: form.schedule_mode,
          delay_seconds: form.delay_seconds,
          cron: form.cron_expression || null,
        },
      },
    };

    try {
      if (isEdit) {
        await update.mutateAsync({ id: workflowId, data: payload });
        navigate('/workflows/list');
      } else {
        const created = await create.mutateAsync(payload);
        navigate(`/workflows/${created.id}/edit`);
      }
    } catch (e) {
      setError(apiErrorMessage(e));
    }
  };

  if ((isEdit && detail.isLoading && !detail.data) || catalog.isLoading) {
    return <WorkflowSkeleton />;
  }

  if (isEdit && detail.error) {
    return <Alert title="Unable to load workflow">{apiErrorMessage(detail.error)}</Alert>;
  }

  return (
    <div className="space-y-6 text-slate-800 dark:text-slate-100">
      <header className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Automation</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
            {isEdit ? 'Edit Workflow' : 'Workflow Builder'}
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            Configure trigger, conditions, schedule, and actions without duplicating business logic.
          </p>
        </div>
        <div className="flex gap-2">
          <Link to="/workflows/list" className="rounded-lg border px-3 py-2 text-sm font-semibold dark:border-slate-600">
            Cancel
          </Link>
          <button
            type="button"
            onClick={() => void save()}
            disabled={saving}
            className="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
          >
            {saving ? 'Saving…' : isEdit ? 'Save Version' : 'Create Workflow'}
          </button>
        </div>
      </header>

      {error ? <Alert title="Validation error">{error}</Alert> : null}

      <WorkflowCanvas definition={form.definition} scheduleMode={form.schedule_mode} />

      <section className="grid gap-4 lg:grid-cols-2">
        <div className="space-y-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
          <div>
            <label className="text-sm font-medium">Name</label>
            <input
              className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            />
          </div>
          <div>
            <label className="text-sm font-medium">Description</label>
            <textarea
              className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
              rows={3}
              value={form.description}
              onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
            />
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <label className="text-sm font-medium">Status</label>
              <select
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                value={form.status}
                onChange={(e) => setForm((f) => ({ ...f, status: e.target.value as WorkflowStatus }))}
              >
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="disabled">Disabled</option>
              </select>
            </div>
            <div>
              <label className="text-sm font-medium">Schedule Mode</label>
              <select
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                value={form.schedule_mode}
                onChange={(e) => setForm((f) => ({ ...f, schedule_mode: e.target.value as ScheduleMode }))}
              >
                <option value="immediate">Immediate</option>
                <option value="delayed">Delayed</option>
                <option value="cron">Cron-based</option>
                <option value="recurring">Recurring</option>
              </select>
            </div>
            <div>
              <label className="text-sm font-medium">Delay (seconds)</label>
              <input
                type="number"
                min={0}
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                value={form.delay_seconds}
                onChange={(e) => setForm((f) => ({ ...f, delay_seconds: Number(e.target.value) }))}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Cron Expression</label>
              <input
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                placeholder="0 * * * *"
                value={form.cron_expression}
                onChange={(e) => setForm((f) => ({ ...f, cron_expression: e.target.value }))}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Max Retries</label>
              <input
                type="number"
                min={0}
                max={10}
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                value={form.max_retries}
                onChange={(e) => setForm((f) => ({ ...f, max_retries: Number(e.target.value) }))}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Retry Delay (seconds)</label>
              <input
                type="number"
                min={0}
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                value={form.retry_delay_seconds}
                onChange={(e) => setForm((f) => ({ ...f, retry_delay_seconds: Number(e.target.value) }))}
              />
            </div>
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_enabled}
              onChange={(e) => setForm((f) => ({ ...f, is_enabled: e.target.checked }))}
            />
            Enabled
          </label>
          {isEdit ? (
            <div>
              <label className="text-sm font-medium">Version Changelog</label>
              <input
                className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950"
                value={form.changelog ?? ''}
                onChange={(e) => setForm((f) => ({ ...f, changelog: e.target.value }))}
                placeholder="Describe this version change"
              />
            </div>
          ) : null}
        </div>

        <div className="space-y-4">
          <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <TriggerSelector
              triggers={triggers}
              value={form.definition.trigger.event_key}
              onChange={(eventKey, sourceModule) =>
                setForm((f) => ({
                  ...f,
                  definition: {
                    ...f.definition,
                    trigger: {
                      event_key: eventKey,
                      source_module: sourceModule || f.definition.trigger.source_module || 'system',
                    },
                  },
                }))
              }
            />
          </div>

          <ConditionBuilder
            value={form.definition.conditions as ConditionRule}
            operators={operators}
            onChange={(conditions) =>
              setForm((f) => ({
                ...f,
                definition: { ...f.definition, conditions },
              }))
            }
          />

          <ActionBuilder
            actions={form.definition.actions}
            catalog={actions}
            onChange={(nextActions) =>
              setForm((f) => ({
                ...f,
                definition: { ...f.definition, actions: nextActions },
              }))
            }
          />
        </div>
      </section>
    </div>
  );
};
