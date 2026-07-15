import { apiClient } from '@/lib/apiClient';

import type {
  ActionCatalogItem,
  ApiCollection,
  ApiResource,
  ExecutionFilters,
  OperatorCatalogItem,
  TriggerCatalogItem,
  WorkflowDashboardData,
  WorkflowDetail,
  WorkflowExecutionItem,
  WorkflowFilters,
  WorkflowFormValues,
  WorkflowItem,
  WorkflowVersionItem,
} from '../types';

const qs = (params: Record<string, unknown> = {}) => {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      search.set(key, String(value));
    }
  });
  const s = search.toString();
  return s ? `?${s}` : '';
};

const attrs = <T>(response: ApiResource<T>) => response.data.attributes;

export const workflowApi = {
  dashboard: async () =>
    attrs((await apiClient.get<ApiResource<WorkflowDashboardData>>('/workflows/dashboard')).data),

  list: async (filters: WorkflowFilters = {}) =>
    (await apiClient.get<ApiCollection<WorkflowItem>>(`/workflows${qs(filters as Record<string, unknown>)}`)).data,

  get: async (id: number) =>
    attrs((await apiClient.get<ApiResource<WorkflowDetail>>(`/workflows/${id}`)).data),

  create: async (data: Partial<WorkflowFormValues>) =>
    attrs((await apiClient.post<ApiResource<WorkflowItem>>('/workflows', data)).data),

  update: async (id: number, data: Partial<WorkflowFormValues>) =>
    attrs((await apiClient.put<ApiResource<WorkflowItem>>(`/workflows/${id}`, data)).data),

  remove: async (id: number) => {
    await apiClient.delete(`/workflows/${id}`);
  },

  enable: async (id: number) =>
    attrs((await apiClient.post<ApiResource<WorkflowItem>>(`/workflows/${id}/enable`)).data),

  disable: async (id: number) =>
    attrs((await apiClient.post<ApiResource<WorkflowItem>>(`/workflows/${id}/disable`)).data),

  pause: async (id: number) =>
    attrs((await apiClient.post<ApiResource<WorkflowItem>>(`/workflows/${id}/pause`)).data),

  resume: async (id: number) =>
    attrs((await apiClient.post<ApiResource<WorkflowItem>>(`/workflows/${id}/resume`)).data),

  clone: async (id: number) =>
    attrs((await apiClient.post<ApiResource<WorkflowItem>>(`/workflows/${id}/clone`)).data),

  run: async (id: number, payload: Record<string, unknown> = {}, dryRun = false) =>
    attrs(
      (
        await apiClient.post<ApiResource<WorkflowExecutionItem>>(`/workflows/${id}/run`, {
          payload,
          dry_run: dryRun,
        })
      ).data,
    ),

  test: async (id: number, payload: Record<string, unknown> = {}) =>
    attrs(
      (await apiClient.post<ApiResource<WorkflowExecutionItem>>(`/workflows/${id}/test`, { payload })).data,
    ),

  versions: async (id: number) =>
    (await apiClient.get<ApiCollection<WorkflowVersionItem>>(`/workflows/${id}/versions`)).data,

  executions: async (filters: ExecutionFilters = {}) =>
    (
      await apiClient.get<ApiCollection<WorkflowExecutionItem>>(
        `/workflows/executions${qs(filters as Record<string, unknown>)}`,
      )
    ).data,

  workflowExecutions: async (id: number, filters: ExecutionFilters = {}) =>
    (
      await apiClient.get<ApiCollection<WorkflowExecutionItem>>(
        `/workflows/${id}/executions${qs(filters as Record<string, unknown>)}`,
      )
    ).data,

  execution: async (executionId: number) =>
    attrs(
      (await apiClient.get<ApiResource<WorkflowExecutionItem>>(`/workflows/executions/${executionId}`)).data,
    ),

  retryExecution: async (executionId: number) =>
    attrs(
      (await apiClient.post<ApiResource<WorkflowExecutionItem>>(`/workflows/executions/${executionId}/retry`))
        .data,
    ),

  cancelExecution: async (executionId: number) =>
    attrs(
      (await apiClient.post<ApiResource<WorkflowExecutionItem>>(`/workflows/executions/${executionId}/cancel`))
        .data,
    ),

  triggerCatalog: async () =>
    (await apiClient.get<ApiCollection<TriggerCatalogItem>>('/workflows/triggers/catalog')).data,

  actionCatalog: async () =>
    (await apiClient.get<ApiCollection<ActionCatalogItem>>('/workflows/actions/catalog')).data,

  operators: async () =>
    (await apiClient.get<ApiCollection<OperatorCatalogItem>>('/workflows/operators')).data,

  catalog: async () =>
    attrs(
      (
        await apiClient.get<
          ApiResource<{
            operators: OperatorCatalogItem[];
            actions: ActionCatalogItem[];
            triggers: TriggerCatalogItem[];
            schedule_modes: string[];
            statuses: string[];
          }>
        >('/workflows/catalog')
      ).data,
    ),
};
