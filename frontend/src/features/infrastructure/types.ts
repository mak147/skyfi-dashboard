export type PopSiteStatus = 'planning' | 'active' | 'maintenance' | 'decommissioned';
export type TowerStatus = 'planning' | 'active' | 'maintenance' | 'decommissioned';
export type SectorStatus = 'planning' | 'active' | 'maintenance' | 'decommissioned';
export type DeviceStatus = 'inventory' | 'deployed' | 'maintenance' | 'offline' | 'decommissioned';
export type TowerType = 'lattice' | 'monopole' | 'guyed' | 'building' | 'water_tank' | 'other';
export type DeviceType = 'router' | 'switch' | 'radio' | 'access_point' | 'olt' | 'onu' | 'ups' | 'other';
export type PowerStatus = 'grid' | 'solar' | 'generator' | 'hybrid' | 'unknown';
export type OwnerType = 'owned' | 'leased' | 'shared' | 'managed';

export interface PopSite {
  id: number;
  name: string;
  code: string;
  address_line1: string | null;
  address_line2: string | null;
  city: string | null;
  region: string | null;
  country: string;
  gps_latitude: string | null;
  gps_longitude: string | null;
  contact_person: string | null;
  contact_phone: string | null;
  contact_email: string | null;
  power_status: PowerStatus;
  fiber_provider: string | null;
  status: PopSiteStatus;
  notes: string | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface Tower {
  id: number;
  pop_site_id: number | null;
  pop_site_name: string | null;
  name: string;
  code: string | null;
  tower_type: TowerType;
  height_meters: string | null;
  owner: OwnerType;
  address_line1: string | null;
  city: string | null;
  region: string | null;
  gps_latitude: string | null;
  gps_longitude: string | null;
  status: TowerStatus;
  notes: string | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface Sector {
  id: number;
  tower_id: number | null;
  tower_name: string | null;
  pop_site_name: string | null;
  name: string;
  azimuth: number;
  beamwidth: number | null;
  frequency_mhz: number;
  channel_width_mhz: number | null;
  ssid: string | null;
  eirp_dbm: number | null;
  device_id: number | null;
  device_name: string | null;
  capacity_mbps: number | null;
  max_subscribers: number | null;
  status: SectorStatus;
  notes: string | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  connection_count?: number;
}

export interface NetworkDevice {
  id: number;
  pop_site_id: number | null;
  pop_site_name: string | null;
  tower_id: number | null;
  tower_name: string | null;
  name: string;
  device_type: DeviceType;
  vendor: string | null;
  model: string | null;
  serial_number: string | null;
  mac_address: string | null;
  ip_address: string | null;
  firmware_version: string | null;
  location_description: string | null;
  management_vlan: number | null;
  management_username: string | null;
  management_password?: string | null;
  status: DeviceStatus;
  notes: string | null;
  mikrotik_router_id: number | null;
  mikrotik_router_name: string | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface PopSiteListFilters {
  search?: string;
  status?: PopSiteStatus | '';
  city?: string;
  region?: string;
  power_status?: PowerStatus | '';
  page?: number;
  per_page?: number;
  sort?: string;
}

export interface TowerListFilters {
  search?: string;
  status?: TowerStatus | '';
  tower_type?: TowerType | '';
  pop_site_id?: number;
  city?: string;
  region?: string;
  page?: number;
  per_page?: number;
  sort?: string;
}

export interface SectorListFilters {
  search?: string;
  status?: SectorStatus | '';
  tower_id?: number;
  device_id?: number;
  frequency_mhz?: number;
  page?: number;
  per_page?: number;
  sort?: string;
}

export interface NetworkDeviceListFilters {
  search?: string;
  status?: DeviceStatus | '';
  device_type?: DeviceType | '';
  pop_site_id?: number;
  tower_id?: number;
  mikrotik_router_id?: number;
  page?: number;
  per_page?: number;
  sort?: string;
}

export interface PaginatedResponse<T> {
  data: Array<{ type: string; id: string; attributes: T }>;
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface InfrastructureDashboardPayload {
  total_pop_sites: number;
  active_pop_sites: number;
  total_towers: number;
  active_towers: number;
  total_sectors: number;
  active_sectors: number;
  total_devices: number;
  active_devices: number;
  offline_devices: number;
  maintenance_devices: number;
  capacity_summary: Array<{
    id: number;
    name: string;
    capacity_mbps: number | null;
    connected_count: number;
  }>;
  status_breakdown: {
    pop_sites: Record<string, number>;
    towers: Record<string, number>;
    sectors: Record<string, number>;
    devices: Record<string, number>;
  };
}
