export type HotspotUserStatus = 'active' | 'disabled' | 'suspended' | 'pending' | 'error';
export type HotspotSyncStatus = 'synced' | 'out_of_sync' | 'missing_on_router' | 'conflict';
export type VoucherStatus = 'new' | 'used' | 'expired' | 'revoked';
export type VoucherBatchStatus = 'active' | 'exhausted' | 'expired' | 'cancelled';

export interface HotspotUser {
  id: number;
  username: string;
  customer_id: number | null;
  customer_name?: string | null;
  connection_id: number | null;
  package_id: number | null;
  router_id: number;
  router_name?: string;
  profile_id: number | null;
  profile_name: string;
  limit_uptime: string | null;
  limit_bytes_in: number | null;
  limit_bytes_out: number | null;
  limit_bytes_total: number | null;
  mac_address: string | null;
  status: HotspotUserStatus;
  sync_status: HotspotSyncStatus;
  last_connected_at?: string | null;
  last_synced_at?: string | null;
  notes: string | null;
  has_password?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface HotspotProfile {
  id: number;
  name: string;
  router_id: number;
  router_name?: string;
  router_profile_name: string;
  rate_limit_up: string | null;
  rate_limit_down: string | null;
  session_timeout: number | null;
  idle_timeout: number | null;
  shared_users: number;
  mac_cookie_timeout: string | null;
  login_methods: string;
  status: 'active' | 'inactive';
  sync_status: HotspotSyncStatus;
  notes: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface Voucher {
  id: number;
  code: string;
  batch_id: number;
  hotspot_user_id: number | null;
  status: VoucherStatus;
  time_limit: string | null;
  data_limit_mb: number | null;
  price: number | null;
  expires_at: string | null;
  used_at: string | null;
  is_expired?: boolean;
  is_available?: boolean;
  created_at?: string;
}

export interface VoucherBatch {
  id: number;
  batch_code: string;
  hotspot_profile_id: number;
  profile_name?: string;
  router_id: number;
  router_name?: string;
  quantity: number;
  prefix: string | null;
  price_per_voucher: number | null;
  time_limit: string | null;
  data_limit_mb: number | null;
  validity_days: number | null;
  status: VoucherBatchStatus;
  notes: string | null;
  created_at?: string;
}

export interface HotspotActiveSession {
  id: string;
  router_id: number;
  router_name?: string;
  username: string;
  mac_address?: string | null;
  ip_address?: string | null;
  uptime: string;
  bytes_in: number;
  bytes_out: number;
  hotspot_user_id?: number | null;
}

export interface HotspotSessionHistory {
  id: number;
  hotspot_user_id: number | null;
  router_id: number;
  session_id: string | null;
  username: string;
  mac_address: string | null;
  ip_address: string | null;
  uptime_seconds: number;
  bytes_in: number;
  bytes_out: number;
  started_at: string;
  ended_at: string | null;
  disconnect_reason: string | null;
}

export interface HotspotSyncDiscrepancy {
  type: 'missing_on_router' | 'orphan_on_router' | 'conflict' | 'connection_error';
  user_id?: number;
  username: string;
  profile?: string;
  disabled?: boolean;
  message: string;
  details?: string[];
}

export interface HotspotSyncResult {
  router_id: number;
  router_name: string;
  status: 'synced' | 'out_of_sync' | 'failed';
  total_users_in_db: number;
  total_users_on_router: number;
  discrepancies: HotspotSyncDiscrepancy[];
  checked_at?: string;
}

export interface HotspotSyncLog {
  id: number;
  router_id: number;
  router_name?: string;
  hotspot_user_id?: number | null;
  hotspot_username?: string;
  action: string;
  status: 'success' | 'failed' | 'warning' | 'conflict';
  message: string;
  diff_payload?: Record<string, unknown> | null;
  created_at: string;
}

export interface MikrotikHotspotProfile {
  id: string;
  name: string;
  rate_limit?: string | null;
  session_timeout?: string | null;
  idle_timeout?: string | null;
  shared_users?: string | null;
  mac_cookie_timeout?: string | null;
  login_by?: string | null;
}

export interface HotspotUserListFilters {
  page?: number;
  perPage?: number;
  search?: string;
  customer_id?: string | number;
  router_id?: string | number;
  profile_id?: string | number;
  status?: HotspotUserStatus | '';
  sync_status?: HotspotSyncStatus | '';
  sort?: string;
}

export interface HotspotUserListResponse {
  data: Array<{
    type: 'hotspot-users';
    id: string;
    attributes: HotspotUser;
  }>;
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface VoucherStats {
  total_new: number;
  total_used: number;
  total_expired: number;
  total_revoked: number;
  daily_logins: number;
}

export interface HotspotUserStatistics {
  total_uptime_seconds: number;
  total_bytes_in: number;
  total_bytes_out: number;
  session_count: number;
}
