export type NotificationStatus = 'unread' | 'read' | 'archived';
export type NotificationSeverity = 'info' | 'success' | 'warning' | 'critical';
export type NotificationChannel = 'in_app' | 'email' | 'sms' | 'push' | 'webhook';
export type DeliveryStatus = 'pending' | 'queued' | 'sent' | 'failed' | 'skipped';

export interface NotificationItem {
  id: number;
  uuid: string;
  recipient_user_id: number;
  notification_type: string;
  category: string;
  title: string;
  body: string;
  data?: Record<string, unknown> | null;
  severity: NotificationSeverity;
  action_url?: string | null;
  status: NotificationStatus;
  read_at?: string | null;
  source_module?: string | null;
  source_event?: string | null;
  source_id?: string | null;
  created_at: string;
  updated_at?: string;
}

export interface NotificationTemplate {
  id: number;
  code: string;
  name: string;
  category: string;
  channel: NotificationChannel;
  subject_template?: string | null;
  body_template: string;
  locale: string;
  is_transactional: number | boolean;
  is_active: number | boolean;
  variables?: string[] | null;
  created_at?: string;
  updated_at?: string;
}

export interface UserPreferenceRow {
  id?: number;
  user_id?: number;
  channel: NotificationChannel;
  category: string;
  is_enabled: number | boolean;
  quiet_hours_start?: string | null;
  quiet_hours_end?: string | null;
  quiet_hours_timezone?: string | null;
}

export interface UserPreferencesPayload {
  user_id: number;
  preferences: UserPreferenceRow[];
  categories: string[];
  channels: string[];
}

export interface DeliveryRecord {
  id: number;
  notification_id?: number | null;
  event_id?: number | null;
  recipient_user_id?: number | null;
  channel: NotificationChannel;
  template_id?: number | null;
  status: DeliveryStatus;
  provider?: string | null;
  provider_message_id?: string | null;
  subject?: string | null;
  body?: string | null;
  fail_reason?: string | null;
  attempt_count: number;
  sent_at?: string | null;
  created_at: string;
}

export interface NotificationEventRecord {
  id: number;
  event_key: string;
  event_uuid: string;
  source_module: string;
  source_id?: string | null;
  payload?: Record<string, unknown>;
  status: string;
  processed_at?: string | null;
  error_message?: string | null;
  created_at: string;
}

export interface NotificationCatalog {
  channels: string[];
  categories: string[];
  types: Record<string, {
    category: string;
    severity: string;
    default_channels: string[];
    action_url?: string | null;
    source_module: string;
  }>;
  severities: string[];
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

export interface NotificationFilters {
  status?: string;
  category?: string;
  type?: string;
  search?: string;
  severity?: string;
  page?: number;
  per_page?: number;
}
