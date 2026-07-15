export type WorkflowStatus = 'draft' | 'active' | 'paused' | 'disabled';
export type ScheduleMode = 'immediate' | 'delayed' | 'cron' | 'recurring';
export type ExecutionStatus =
  | 'pending'
  | 'scheduled'
  | 'running'
  | 'success'
  | 'failed'
  | 'partial'
  | 'skipped'
  | 'cancelled'
  | 'paused';
export type TriggerSource = 'event' | 'manual' | 'test' | 'schedule' | 'recurring';

export interface ApiResource<T> {
  data: {
    type: string;
    id: string;
    attributes: T;
  };
}

export interface ApiCollection<T> {
  data: Array<{ type: string; id: string; attributes: T }>;
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface ConditionRule {
  field?: string;
  operator?: string;
  value?: unknown;
  logic?: 'AND' | 'OR';
  rules?: ConditionRule[];
}

export interface WorkflowActionDef {
  type: string;
  name?: string | null;
  config?: Record<string, unknown>;
  order?: number;
  continue_on_failure?: boolean;
  is_enabled?: boolean;
}

export interface WorkflowDefinition {
  trigger: {
    event_key: string;
    source_module?: string;
    filter?: Record<string, unknown> | null;
  };
  conditions: ConditionRule;
  actions: WorkflowActionDef[];
  schedule?: {
    mode?: ScheduleMode;
    delay_seconds?: number;
    cron?: string | null;
  };
}

export interface WorkflowItem {
  id: number;
  uuid: string;
  name: string;
  description: string | null;
  status: WorkflowStatus;
  is_enabled: boolean;
  active_version_id: number | null;
  trigger_event_key: string | null;
  schedule_mode: ScheduleMode;
  cron_expression: string | null;
  delay_seconds: number;
  max_retries: number;
  retry_delay_seconds: number;
  last_executed_at: string | null;
  execution_count: number;
  success_count: number;
  failure_count: number;
  created_by: number | null;
  updated_by: number | null;
  created_at: string;
  updated_at?: string;
}

export interface WorkflowVersionItem {
  id: number;
  workflow_id: number;
  version_number: number;
  definition: WorkflowDefinition;
  changelog: string | null;
  is_published: boolean;
  created_by: number | null;
  created_at: string;
}

export interface WorkflowDetail {
  workflow: WorkflowItem;
  active_version: WorkflowVersionItem | null;
  definition: WorkflowDefinition;
  triggers: Array<Record<string, unknown>>;
  conditions: Array<Record<string, unknown>>;
  actions: Array<Record<string, unknown>>;
  versions: WorkflowVersionItem[];
}

export interface WorkflowExecutionItem {
  id: number;
  uuid: string;
  workflow_id: number;
  version_id: number;
  workflow_name?: string;
  trigger_event_key: string | null;
  trigger_payload: Record<string, unknown> | null;
  trigger_source: TriggerSource;
  status: ExecutionStatus;
  scheduled_at: string | null;
  started_at: string | null;
  finished_at: string | null;
  duration_ms: number | null;
  attempt_number: number;
  max_attempts: number;
  next_retry_at: string | null;
  result_json: Record<string, unknown> | null;
  action_results: Array<Record<string, unknown>> | null;
  error_message: string | null;
  actor_user_id: number | null;
  created_at: string;
  updated_at?: string;
}

export interface WorkflowDashboardData {
  workflows: {
    total: number;
    active: number;
    paused: number;
    draft: number;
    disabled: number;
  };
  lifetime: {
    executions: number;
    success: number;
    failures: number;
  };
  last_24h: {
    executions: number;
    success: number;
    failed: number;
    in_flight: number;
  };
  recent_executions: WorkflowExecutionItem[];
}

export interface ActionCatalogItem {
  type: string;
  label: string;
  module: string;
  description: string;
  config_schema: Record<string, { type: string; required?: boolean }>;
}

export interface TriggerCatalogItem {
  id?: number;
  event_key: string;
  source_module: string;
  description?: string | null;
  payload_schema?: Record<string, unknown> | null;
  is_active?: boolean;
}

export interface OperatorCatalogItem {
  id: string;
  label: string;
  value_type: string;
}

export interface WorkflowFilters {
  search?: string;
  status?: string;
  trigger_event_key?: string;
  is_enabled?: boolean | string;
  page?: number;
  per_page?: number;
}

export interface ExecutionFilters {
  workflow_id?: number;
  status?: string;
  trigger_event_key?: string;
  trigger_source?: string;
  search?: string;
  from?: string;
  to?: string;
  page?: number;
  per_page?: number;
}

export interface WorkflowFormValues {
  name: string;
  description: string;
  status: WorkflowStatus;
  is_enabled: boolean;
  schedule_mode: ScheduleMode;
  cron_expression: string;
  delay_seconds: number;
  max_retries: number;
  retry_delay_seconds: number;
  definition: WorkflowDefinition;
  changelog?: string;
}
