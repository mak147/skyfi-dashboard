export type AuditSeverity = 'info' | 'warning' | 'critical';
export type AuditExportStatus = 'pending' | 'processing' | 'completed' | 'failed';
export type CompliancePolicyType = 'data_retention' | 'access_control' | 'immutability' | 'privacy' | 'custom';
export type AuditExportFormat = 'csv' | 'json';

export interface AuditLog {
  id: number;
  user_id: number | null;
  user_name?: string | null;
  user_email?: string | null;
  action: string;
  entity_type: string;
  entity_id: number | null;
  module: string | null;
  resource: string | null;
  severity: AuditSeverity;
  correlation_id: string | null;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  url: string | null;
  compliance_tags: string[] | null;
  is_immutable: number | boolean;
  created_at: string;
}

export interface ActivityEvent {
  id: number;
  user_id: number | null;
  user_name?: string | null;
  user_email?: string | null;
  module: string;
  action: string;
  resource_type: string;
  resource_id: number | null;
  description: string | null;
  ip_address: string | null;
  user_agent: string | null;
  metadata: Record<string, unknown> | null;
  correlation_id: string | null;
  created_at: string;
}

export interface CompliancePolicy {
  id: number;
  name: string;
  description: string | null;
  policy_type: CompliancePolicyType;
  rules: Record<string, unknown>;
  is_active: number | boolean;
  created_by: number | null;
  created_at: string;
  updated_at: string;
}

export interface RetentionPolicy {
  id: number;
  name: string;
  description: string | null;
  module: string;
  action_pattern: string;
  retention_days: number;
  auto_archive: number | boolean;
  archive_location: string | null;
  is_active: number | boolean;
  created_by: number | null;
  created_at: string;
  updated_at: string;
}

export interface AuditExport {
  id: number;
  user_id: number;
  format: AuditExportFormat;
  filters: Record<string, unknown> | null;
  row_count: number;
  file_path: string | null;
  status: AuditExportStatus;
  error_message: string | null;
  started_at: string | null;
  completed_at: string | null;
  created_at: string;
}

export interface AuditDashboardStats {
  total_logs: number;
  today_count: number;
  week_count: number;
  month_count: number;
  critical_count: number;
  by_module: Array<{ module: string; count: number }>;
  top_actions: Array<{ action: string; count: number }>;
  recent_activity: AuditLog[];
}

export interface AuditFilterOptions {
  modules: string[];
  actions: string[];
  entity_types: string[];
  severities: string[];
}

export interface AuditLogFilters {
  module?: string;
  action?: string;
  entity_type?: string;
  entity_id?: number;
  user_id?: number;
  severity?: string;
  correlation_id?: string;
  date_from?: string;
  date_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface ActivityFilters {
  user_id?: number;
  module?: string;
  action?: string;
  resource_type?: string;
  resource_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}

export interface ExportRequest {
  format?: AuditExportFormat;
  module?: string;
  action?: string;
  entity_type?: string;
  entity_id?: number;
  user_id?: number;
  severity?: string;
  date_from?: string;
  date_to?: string;
  search?: string;
}

export interface PageMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ApiCollection<T> {
  data: Array<{ type: string; id: string; attributes: T }>;
  meta: PageMeta;
}

export interface ApiResource<T> {
  data: { type: string; id: string; attributes: T };
}
