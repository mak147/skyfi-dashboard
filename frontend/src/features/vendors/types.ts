export type VendorStatus = 'active' | 'inactive' | 'on_hold';
export type ContractStatus = 'draft' | 'active' | 'expiring' | 'expired' | 'terminated';
export type QuotationStatus = 'received' | 'under_review' | 'accepted' | 'rejected' | 'expired';

export interface Vendor {
  id: number;
  code: string;
  name: string;
  status: VendorStatus;
  contact_name: string | null;
  email: string | null;
  phone: string | null;
  website: string | null;
  tax_id: string | null;
  registration_number: string | null;
  address: string | null;
  city: string | null;
  country: string;
  payment_terms: string | null;
  currency: string;
  category: string;
  overall_rating: number;
  notes: string | null;
  contacts_count?: number;
  contracts_count?: number;
  created_by?: number;
  created_by_name?: string | null;
  created_at: string;
  updated_at?: string;
  performance_metrics?: VendorPerformanceMetrics;
}

export interface VendorContact {
  id: number;
  vendor_id: number;
  vendor_name?: string;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  department: string | null;
  position: string | null;
  is_primary: boolean | number;
  is_emergency: boolean | number;
  notes: string | null;
  created_at?: string;
}

export interface VendorContract {
  id: number;
  vendor_id: number;
  vendor_name?: string;
  contract_number: string;
  title: string;
  start_date: string;
  end_date: string;
  renewal_date: string | null;
  contract_value: number | string;
  currency: string;
  status: ContractStatus;
  attachment_path: string | null;
  notes: string | null;
  created_at?: string;
}

export interface VendorQuotationItem {
  id?: number;
  product_id?: number | null;
  product_name?: string | null;
  product_sku?: string | null;
  description: string;
  quantity: number | string;
  unit_price: number | string;
  line_total?: number | string;
  notes?: string | null;
}

export interface VendorQuotation {
  id: number;
  vendor_id: number;
  vendor_name?: string;
  purchase_request_id?: number | null;
  purchase_request_number?: string | null;
  rfq_number?: string | null;
  quotation_number: string;
  quotation_date: string;
  validity_date: string;
  total_amount: number | string;
  currency: string;
  status: QuotationStatus;
  notes: string | null;
  item_count?: number;
  items?: VendorQuotationItem[];
  created_at?: string;
}

export interface VendorRating {
  id: number;
  vendor_id: number;
  vendor_name?: string;
  evaluator_name?: string;
  evaluation_date: string;
  delivery_performance: number | string;
  order_completion: number | string;
  product_quality: number | string;
  return_rate: number | string;
  average_lead_time_days: number | string;
  overall_score: number | string;
  evaluator_user_id?: number;
  comments?: string | null;
  created_at?: string;
}

export interface VendorPerformanceMetrics {
  vendor_id: number;
  total_orders: number;
  completed_orders: number;
  order_completion: number;
  procurement_value: number;
  return_rate: number;
  product_quality: number;
  delivery_performance: number;
  average_lead_time_days: number;
  overall_rating: number;
}

export interface VendorDashboardWidgets {
  active_suppliers: number;
  total_suppliers: number;
  expiring_contracts_count: number;
  average_supplier_rating: number;
  total_procurement_spend: number;
  top_suppliers: {
    id: number;
    code: string;
    name: string;
    category: string;
    overall_rating: number | string;
    po_count: number | string;
    total_spend: number | string;
  }[];
  expiring_contracts_list: {
    id: number;
    contract_number: string;
    title: string;
    end_date: string;
    status: ContractStatus;
    contract_value: number | string;
    vendor_name: string;
  }[];
}

export interface VendorPurchasingHistory {
  purchase_orders: {
    id: number;
    po_number: string;
    order_date: string;
    status: string;
    total_amount: number | string;
    currency: string;
  }[];
  supplier_invoices: {
    id: number;
    invoice_number: string;
    invoice_date: string;
    status: string;
    total_amount: number | string;
    currency: string;
  }[];
  catalog_products: {
    id: number;
    sku: string;
    name: string;
    vendor_sku: string | null;
    last_purchase_cost: number | string;
    lead_time_days: number | string;
  }[];
  total_procurement_spend: number;
}

export interface ListMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface Resource<T> {
  type: string;
  id: string;
  attributes: T;
}

export interface PaginatedResponse<T> {
  data: Resource<T>[];
  meta: ListMeta;
}

export interface VendorFormValues {
  code: string;
  name: string;
  status: VendorStatus;
  contact_name?: string;
  email?: string;
  phone?: string;
  website?: string;
  tax_id?: string;
  registration_number?: string;
  address?: string;
  city?: string;
  country: string;
  payment_terms?: string;
  currency: string;
  category: string;
  notes?: string;
}

export interface VendorContactFormValues {
  vendor_id?: number;
  first_name: string;
  last_name: string;
  email?: string;
  phone?: string;
  department?: string;
  position?: string;
  is_primary: boolean;
  is_emergency: boolean;
  notes?: string;
}

export interface VendorContractFormValues {
  vendor_id?: number;
  contract_number: string;
  title: string;
  start_date: string;
  end_date: string;
  renewal_date?: string;
  contract_value: number;
  currency: string;
  status: ContractStatus;
  attachment_path?: string;
  notes?: string;
}

export interface VendorQuotationFormValues {
  vendor_id?: number;
  purchase_request_id?: number;
  rfq_number?: string;
  quotation_number: string;
  quotation_date: string;
  validity_date: string;
  total_amount: number;
  currency: string;
  status: QuotationStatus;
  notes?: string;
  items: VendorQuotationItem[];
}

export interface VendorRatingFormValues {
  vendor_id: number;
  evaluation_date: string;
  delivery_performance: number;
  order_completion: number;
  product_quality: number;
  return_rate: number;
  average_lead_time_days: number;
  comments?: string;
}
