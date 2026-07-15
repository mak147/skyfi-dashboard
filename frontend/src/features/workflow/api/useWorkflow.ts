import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { workflowApi } from './workflowApi';
import type { ExecutionFilters, WorkflowFilters, WorkflowFormValues } from '../types';

export const useWorkflowDashboard = () =>
  useQuery({
    queryKey: ['workflow', 'dashboard'],
    queryFn: () => workflowApi.dashboard(),
    staleTime: 30_000,
  });

export const useWorkflows = (filters: WorkflowFilters = {}) =>
  useQuery({
    queryKey: ['workflow', 'list', filters],
    queryFn: () => workflowApi.list(filters),
  });

export const useWorkflow = (id: number) =>
  useQuery({
    queryKey: ['workflow', 'detail', id],
    queryFn: () => workflowApi.get(id),
    enabled: id > 0,
  });

export const useWorkflowExecutions = (filters: ExecutionFilters = {}) =>
  useQuery({
    queryKey: ['workflow', 'executions', filters],
    queryFn: () => workflowApi.executions(filters),
  });

export const useWorkflowCatalog = () =>
  useQuery({
    queryKey: ['workflow', 'catalog'],
    queryFn: () => workflowApi.catalog(),
    staleTime: 120_000,
  });

export const useTriggerCatalog = () =>
  useQuery({
    queryKey: ['workflow', 'triggers'],
    queryFn: () => workflowApi.triggerCatalog(),
    staleTime: 120_000,
  });

export const useActionCatalog = () =>
  useQuery({
    queryKey: ['workflow', 'actions'],
    queryFn: () => workflowApi.actionCatalog(),
    staleTime: 120_000,
  });

export const useCreateWorkflow = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<WorkflowFormValues>) => workflowApi.create(data),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['workflow'] });
    },
  });
};

export const useUpdateWorkflow = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<WorkflowFormValues> }) =>
      workflowApi.update(id, data),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['workflow'] });
    },
  });
};

export const useWorkflowAction = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      id,
      action,
      payload,
    }: {
      id: number;
      action: 'enable' | 'disable' | 'pause' | 'resume' | 'clone' | 'run' | 'test' | 'delete';
      payload?: Record<string, unknown>;
    }) => {
      switch (action) {
        case 'enable':
          return workflowApi.enable(id);
        case 'disable':
          return workflowApi.disable(id);
        case 'pause':
          return workflowApi.pause(id);
        case 'resume':
          return workflowApi.resume(id);
        case 'clone':
          return workflowApi.clone(id);
        case 'run':
          return workflowApi.run(id, payload ?? {});
        case 'test':
          return workflowApi.test(id, payload ?? {});
        case 'delete':
          await workflowApi.remove(id);
          return null;
        default:
          return null;
      }
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['workflow'] });
    },
  });
};

export const useExecutionAction = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      executionId,
      action,
    }: {
      executionId: number;
      action: 'retry' | 'cancel';
    }) => {
      if (action === 'retry') return workflowApi.retryExecution(executionId);
      return workflowApi.cancelExecution(executionId);
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['workflow', 'executions'] });
      void qc.invalidateQueries({ queryKey: ['workflow', 'dashboard'] });
    },
  });
};
