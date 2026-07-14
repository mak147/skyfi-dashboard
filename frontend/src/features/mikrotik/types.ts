export type RouterStatus = 'online' | 'offline' | 'unknown' | 'disabled';

export interface RouterTag {
  id: number;
  name: string;
  color: string | null;
}

export interface RouterGroup {
  id: number;
  name: string;
  description: string | null;
  router_count?: number;
}

export interface MikrotikRouter {
  id: number;
  router_group_id: number | null;
  router_group_name?: string | null;
  name: string;
  host: string;
  api_port: number;
  api_username: string;
  routeros_version: string | null;
  model: string | null;
  location: string | null;
  site: string | null;
  notes: string | null;
  is_enabled: boolean;
  has_credentials: boolean;
  last_connection_status: RouterStatus;
  last_connection_error: string | null;
  last_connected_at: string | null;
  last_discovered_at: string | null;
  last_health_checked_at: string | null;
  created_at: string;
  updated_at: string;
  tags: RouterTag[];
}

export interface RouterFormData {
  name: string;
  host: string;
  api_port: number;
  api_username: string;
  api_password?: string;
  router_group_id: number | null;
  tag_ids: number[];
  location: string;
  site: string;
  notes: string;
  is_enabled: boolean;
}

export interface RouterListFilters {
  search?: string;
  router_group_id?: string;
  tag_id?: string;
  site?: string;
  status?: RouterStatus | '';
  is_enabled?: '' | 'true' | 'false';
}

export interface RouterListResponse {
  data: Array<{ type: 'mikrotik-routers'; id: string; attributes: MikrotikRouter }>;
  meta: { current_page: number; per_page: number; total: number; last_page: number };
}

export interface RouterInterface {
  id: string | null;
  name: string | null;
  type: string | null;
  running: boolean;
  disabled: boolean;
  mtu: number | null;
  rx_bytes: number | null;
  tx_bytes: number | null;
}

export interface RouterIpAddress {
  id: string | null;
  address: string | null;
  network: string | null;
  interface: string | null;
  disabled: boolean;
}

export interface RouterDiscovery {
  identity: string | null;
  routeros_version: string | null;
  model: string | null;
  uptime: string | null;
  cpu_usage_percent: number | null;
  memory_total_bytes: number | null;
  memory_free_bytes: number | null;
  disk_total_bytes: number | null;
  disk_free_bytes: number | null;
  interfaces: RouterInterface[];
  ip_addresses: RouterIpAddress[];
  active_users_count: number;
  queue_count: number;
  latency_ms: number;
  discovered_at: string;
}

export interface RouterHealth {
  id: number;
  router_id: number;
  status: RouterStatus;
  latency_ms: number | null;
  cpu_usage_percent: number | null;
  memory_total_bytes: number | null;
  memory_free_bytes: number | null;
  disk_total_bytes: number | null;
  disk_free_bytes: number | null;
  temperature_celsius: number | null;
  traffic_rx_bytes: number | null;
  traffic_tx_bytes: number | null;
  active_users_count: number | null;
  queue_count: number | null;
  uptime: string | null;
  error_message: string | null;
  checked_at: string;
}

export interface ConnectionTestResult {
  connected: boolean;
  latency_ms: number;
  identity: string | null;
  routeros_version: string | null;
  model: string | null;
}
