export type ConnectionType = 'pppoe' | 'hotspot' | 'static_ip';

export type ConnectionStatus =
  | 'pending'
  | 'scheduled'
  | 'installing'
  | 'active'
  | 'suspended'
  | 'disconnected'
  | 'cancelled'
  | 'archived';

export interface Connection {
  id: number;
  connection_number: string;
  name: string;
  customer_id: number;
  package_id: number;
  type: ConnectionType;
  status: ConnectionStatus;
  
  // Network
  pppoe_username?: string;
  pppoe_password?: string;
  static_ip?: string;
  gateway?: string;
  dns_servers?: string;
  mac_address?: string;
  vlan_id?: number;
  radius_profile?: string;
  queue_name?: string;

  // Infrastructure
  pop_site?: string;
  tower?: string;
  sector?: string;
  access_point?: string;
  assigned_router?: string;

  // Installation
  installation_date?: string;
  activation_date?: string;
  installation_team?: string;
  technician_id?: number;
  installation_cost: number;
  installation_notes?: string;

  // Billing Summary
  billing_start_date?: string;
  next_billing_date?: string;
  contract_length_months: number;
  auto_renew: boolean;
  grace_period_days: number;

  // Monitoring
  last_online_at?: string;

  // Audit
  created_by: number;
  updated_by?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;

  // Joined fields
  customer_name?: string;
  package_name?: string;
}

export interface ConnectionFilters {
  status?: ConnectionStatus | '';
  type?: ConnectionType | '';
  customer_id?: number;
  package_id?: number;
  search?: string;
}

export interface ConnectionListResponse {
  data: Array<{
    type: 'connections';
    id: string;
    attributes: Connection;
  }>;
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface ConnectionFormData {
  name: string;
  customer_id: number;
  package_id: number;
  type: ConnectionType;
  pppoe_username?: string;
  pppoe_password?: string;
  static_ip?: string;
  gateway?: string;
  dns_servers?: string;
  mac_address?: string;
  vlan_id?: number;
  radius_profile?: string;
  queue_name?: string;
  pop_site?: string;
  tower?: string;
  sector?: string;
  access_point?: string;
  assigned_router?: string;
  installation_date?: string;
  installation_team?: string;
  technician_id?: number;
  installation_cost: number;
  installation_notes?: string;
}
