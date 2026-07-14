export type PppoeStatus = 'active' | 'disabled' | 'suspended' | 'pending' | 'error';
export type PppoeSyncStatus = 'synced' | 'out_of_sync' | 'missing_on_router' | 'conflict';

export interface PppoeAccount {
  id: number;
  username: string;
  customer_id: number;
  customer_name?: string;
  connection_id: number;
  connection_number?: string;
  package_id: number;
  package_name?: string;
  router_id: number;
  router_name?: string;
  profile: string;
  service: string;
  ip_pool: string | null;
  static_ip: string | null;
  mac_binding: string | null;
  caller_id: string | null;
  rate_limit: string | null;
  session_timeout: number | null;
  idle_timeout: number | null;
  shared_users: number;
  status: PppoeStatus;
  sync_status: PppoeSyncStatus;
  last_connected_at?: string | null;
  last_synced_at?: string | null;
  notes: string | null;
  has_password?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface PppoeActiveSession {
  id: string;
  router_id: number;
  router_name?: string;
  username: string;
  service: string;
  caller_id?: string | null;
  ip_address?: string | null;
  uptime: string;
  encoding?: string | null;
  account_id?: number | null;
  customer_id?: number | null;
}

export interface PppoeSessionHistory {
  id: number;
  account_id: number;
  router_id: number;
  session_id: string;
  username: string;
  ip_address: string;
  mac_address: string | null;
  caller_id: string | null;
  uptime_seconds: number;
  bytes_in: number;
  bytes_out: number;
  started_at: string;
  ended_at: string;
  disconnect_reason: string | null;
}

export interface PppoeSyncDiscrepancy {
  type: 'missing_on_router' | 'orphan_on_router' | 'conflict' | 'connection_error';
  account_id?: number;
  username: string;
  profile?: string;
  disabled?: boolean;
  message: string;
  details?: string[];
}

export interface PppoeSyncResult {
  router_id: number;
  router_name: string;
  status: 'synced' | 'out_of_sync' | 'failed';
  total_accounts_in_db: number;
  total_secrets_on_router: number;
  discrepancies: PppoeSyncDiscrepancy[];
  checked_at?: string;
}

export interface PppoeSyncLog {
  id: number;
  router_id: number;
  router_name?: string;
  account_id?: number | null;
  account_username?: string;
  action: 'sync_user' | 'sync_router' | 'detect_missing' | 'repair_user' | 'import_users' | 'conflict_resolved';
  status: 'success' | 'failed' | 'conflict' | 'warning';
  message: string;
  diff_payload?: Record<string, unknown> | null;
  created_at: string;
}

export interface MikrotikPppProfile {
  id: string;
  name: string;
  local_address?: string;
  remote_address?: string;
  rate_limit?: string;
  only_one?: string;
}

export interface PppoeListFilters {
  page?: number;
  perPage?: number;
  search?: string;
  customer_id?: string | number;
  connection_id?: string | number;
  package_id?: string | number;
  router_id?: string | number;
  status?: PppoeStatus | '';
  sync_status?: PppoeSyncStatus | '';
  sort?: string;
}

export interface PppoeListResponse {
  data: Array<{
    type: 'pppoe-accounts';
    id: string;
    attributes: PppoeAccount;
  }>;
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface PppoeAccountStatistics {
  total_uptime_seconds: number;
  total_bytes_in: number;
  total_bytes_out: number;
  session_count: number;
}
