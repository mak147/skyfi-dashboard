export type WebhookDeliveryStatus = 'pending' | 'sent' | 'failed' | 'retrying';
export type ConnectorCategory = 'payment' | 'messaging' | 'mapping';

export interface ApiKeyItem {
  id: number;
  client_application_id: number | null;
  name: string;
  key_prefix: string;
  scopes: string[];
  ip_allow_list: string[] | null;
  is_active: boolean;
  rate_limit_per_minute: number | null;
  last_used_at: string | null;
  expires_at: string | null;
  created_by: number | null;
  created_at: string;
  updated_at?: string;
}

export interface ClientApplicationItem {
  id: number;
  name: string;
  description: string | null;
  redirect_uris: string[] | null;
  is_active: boolean;
  rate_limit_per_minute: number;
  created_by: number | null;
  created_at: string;
  updated_at?: string;
}

export interface WebhookItem {
  id: number;
  client_application_id: number | null;
  name: string;
  url: string;
  events: string[];
  is_active: boolean;
  is_inbound: boolean;
  retry_policy: { max_attempts: number; backoff: string };
  filter_rules: Record<string, unknown> | null;
  content_type: string;
  created_by: number | null;
  created_at: string;
  updated_at?: string;
}

export interface WebhookDeliveryItem {
  id: number;
  webhook_id: number;
  event_id: number | null;
  event_key: string;
  payload: Record<string, unknown>;
  request_headers: Record<string, string> | null;
  response_status_code: number | null;
  response_body: string | null;
  response_headers: Record<string, string> | null;
  attempt_number: number;
  status: WebhookDeliveryStatus;
  next_retry_at: string | null;
  error_message: string | null;
  duration_ms: number | null;
  created_at: string;
  updated_at?: string;
}

export interface EventRegistryItem {
  id: number;
  event_key: string;
  source_module: string;
  description: string | null;
  payload_schema: Record<string, unknown> | null;
  is_active: boolean;
  created_at: string;
  updated_at?: string;
}

export interface ConnectorItem {
  id: number;
  connector_type: string;
  name: string;
  description: string | null;
  config: Record<string, unknown>;
  is_enabled: boolean;
  rate_limit_per_minute: number | null;
  created_by: number | null;
  created_at: string;
  updated_at?: string;
  _meta?: {
    category: ConnectorCategory;
    default_config: Record<string, unknown>;
  };
}

export interface ApiRequestLogItem {
  id: number;
  api_key_id: number | null;
  client_application_id: number | null;
  method: string;
  path: string;
  status_code: number;
  ip_address: string;
  user_agent: string | null;
  request_headers: Record<string, string> | null;
  request_body: Record<string, unknown> | null;
  response_body: Record<string, unknown> | null;
  duration_ms: number | null;
  created_at: string;
}

export interface IntegrationDashboardData {
  api_keys: { total: number; active: number };
  webhooks: { total: number; outbound: number; inbound: number };
  deliveries: { failed: number; pending_retries: number };
  events: { total: number; source_modules: string[] };
  connectors: { total: number; enabled: number };
  request_stats: {
    total_requests: number;
    success_count: number;
    error_count: number;
    avg_duration_ms: number | null;
    unique_api_keys: number;
    unique_endpoints: number;
  };
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
  data: { type: string; id: string; attributes: T; meta?: Record<string, unknown> };
}

export interface IntegrationFilters {
  page?: number;
  per_page?: number;
  search?: string;
  is_active?: boolean;
  client_application_id?: number;
  source_module?: string;
  event_key?: string;
  status?: string;
  webhook_id?: number;
}
