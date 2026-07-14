export type InvoiceStatus = 'draft' | 'pending' | 'issued' | 'partially_paid' | 'paid' | 'overdue' | 'cancelled' | 'void';
export type InvoiceItemType = 'recurring' | 'one_time' | 'installation' | 'prorated' | 'late_fee' | 'discount' | 'tax' | 'custom';

export interface InvoiceItem {
  id: number;
  invoice_id: number;
  item_type: InvoiceItemType;
  description: string;
  quantity: number;
  unit_price: number;
  amount: number;
  tax_amount: number;
  discount_amount: number;
  created_at: string;
}

export interface InvoiceActivity {
  id: number;
  invoice_id: number;
  action: string;
  description: string | null;
  performed_by: number | null;
  performed_by_name: string | null;
  created_at: string;
}

export interface Invoice {
  id: number;
  invoice_number: string;
  customer_id: number;
  connection_id: number;
  package_id: number;
  status: InvoiceStatus;
  billing_period_start: string;
  billing_period_end: string;
  issue_date: string;
  due_date: string;
  currency: string;
  subtotal: number;
  tax_amount: number;
  discount_amount: number;
  late_fee_amount: number;
  previous_balance: number;
  total_amount: number;
  balance_due: number;
  notes: string | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  customer_name: string | null;
  customer_code: string | null;
  connection_number: string | null;
  package_name: string | null;
  items: InvoiceItem[];
  activities: InvoiceActivity[];
}

export interface InvoiceFilters {
  status?: InvoiceStatus | '';
  customer_id?: string;
  due_from?: string;
  due_to?: string;
  search?: string;
}

export interface InvoiceListMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface InvoiceListResponse {
  data: Array<{
    type: 'invoices';
    id: string;
    attributes: Invoice;
  }>;
  links: {
    self: string;
    first: string;
    last: string;
    prev?: string;
    next?: string;
  };
  meta: InvoiceListMeta;
}

export interface InvoiceFormData {
  customer_id: string;
  connection_id: string;
  package_id: string;
  billing_period_start: string;
  billing_period_end: string;
  issue_date: string;
  due_date: string;
  notes: string;
  previous_balance: string;
  items: Array<{
    item_type: InvoiceItemType;
    description: string;
    quantity: string;
    unit_price: string;
    tax_amount: string;
    discount_amount: string;
  }>;
}

export interface GenerateInvoiceData {
  connection_id: string;
  billing_period_start?: string;
  billing_period_end?: string;
  issue_date?: string;
  due_date?: string;
  notes?: string;
}

export interface BulkGenerateData {
  billing_date?: string;
  connection_ids?: number[];
}

export interface BillingStatistics {
  invoices_today: number;
  invoices_this_month: number;
  pending_invoices: number;
  paid_invoices: number;
  overdue_invoices: number;
}
