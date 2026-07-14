export type CustomerStatus = 'lead' | 'prospect' | 'active' | 'suspended' | 'disconnected' | 'archived';
export type ConnectionStatus = 'pending' | 'installed' | 'active' | 'down';

export interface Customer {
  id: number;
  customer_code: string;
  full_name: string;
  father_husband_name: string | null;
  cnic: string | null;
  phone: string;
  whatsapp: string | null;
  email: string | null;
  address: string;
  city: string;
  area: string;
  notes: string | null;
  status: CustomerStatus;
  registration_date: string | null;
  installation_date: string | null;
  assigned_package_id: number | null;
  connection_status: ConnectionStatus | null;
  installation_technician_id: number | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface CustomerFilters {
  status?: CustomerStatus | '';
  city?: string;
  area?: string;
  search?: string;
}

export interface CustomerListMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface CustomerListResponse {
  data: Array<{
    type: 'customers';
    id: string;
    attributes: Customer;
  }>;
  links: {
    self: string;
    first: string;
    last: string;
    prev?: string;
    next?: string;
  };
  meta: CustomerListMeta;
}

export interface CustomerFormData {
  full_name: string;
  father_husband_name: string;
  cnic: string;
  phone: string;
  whatsapp: string;
  email: string;
  address: string;
  city: string;
  area: string;
  notes: string;
  registration_date: string;
  installation_date: string;
  installation_technician_id: string;
  emergency_contact_name: string;
  emergency_contact_phone: string;
}
