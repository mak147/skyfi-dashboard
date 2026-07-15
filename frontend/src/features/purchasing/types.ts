export type PurchaseRequestStatus = 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'cancelled' | 'converted';
export type PurchaseOrderStatus = 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'sent' | 'partially_received' | 'fully_received' | 'closed' | 'cancelled';
export type GoodsReceiptStatus = 'received' | 'partial' | 'returned';
export type SupplierInvoiceStatus = 'draft' | 'registered' | 'verified' | 'disputed' | 'paid';
export type Priority = 'low' | 'normal' | 'high' | 'urgent';

export interface PurchaseRequestItem {
  id: number;
  product_id: number;
  product_name: string;
  sku: string;
  unit_name: string;
  unit_symbol: string;
  description: string | null;
  quantity: string;
  estimated_unit_cost: string;
  notes: string | null;
}

export interface ApprovalRecord {
  id: number;
  approver_user_id: number;
  approver_name: string;
  decision: 'approved' | 'rejected';
  comments: string | null;
  decided_at: string;
}

export interface PurchaseRequest {
  id: number;
  request_number: string;
  requester_user_id: number;
  requester_name: string;
  department: string | null;
  priority: Priority;
  required_date: string | null;
  status: PurchaseRequestStatus;
  notes: string | null;
  item_count: number;
  total_quantity: number;
  created_at: string;
  updated_at: string;
  items?: PurchaseRequestItem[];
  approvals?: ApprovalRecord[];
}

export interface PurchaseOrderItem {
  id: number;
  product_id: number;
  product_name: string;
  sku: string;
  unit_name: string;
  unit_symbol: string;
  description: string | null;
  quantity_ordered: string;
  quantity_received: string;
  quantity_damaged: string;
  quantity_returned: string;
  unit_price: string;
  line_total: string;
  notes: string | null;
}

export interface PurchaseOrder {
  id: number;
  po_number: string;
  vendor_id: number;
  vendor_name: string;
  warehouse_id: number | null;
  warehouse_name: string | null;
  purchase_request_id: number | null;
  purchase_request_number: string | null;
  currency: string;
  tax_rate: string;
  discount_amount: string;
  subtotal: string;
  tax_amount: string;
  total_amount: string;
  order_date: string;
  expected_delivery_date: string | null;
  delivery_date: string | null;
  status: PurchaseOrderStatus;
  notes: string | null;
  item_count: number;
  total_ordered: number;
  total_received: number;
  created_at: string;
  items?: PurchaseOrderItem[];
  approvals?: ApprovalRecord[];
}

export interface GoodsReceiptItem {
  id: number;
  purchase_order_item_id: number;
  product_id: number;
  product_name: string;
  sku: string;
  unit_name: string;
  unit_symbol: string;
  quantity_accepted: string;
  quantity_damaged: string;
  quantity_short: string;
  warehouse_location_id: number | null;
  location_code: string | null;
  location_name: string | null;
  condition: string;
  notes: string | null;
}

export interface GoodsReceipt {
  id: number;
  receipt_number: string;
  purchase_order_id: number;
  po_number: string;
  vendor_name: string;
  warehouse_id: number | null;
  warehouse_name: string | null;
  status: GoodsReceiptStatus;
  received_by: number;
  received_by_name: string;
  received_at: string;
  notes: string | null;
  items?: GoodsReceiptItem[];
}

export interface SupplierInvoice {
  id: number;
  invoice_number: string;
  vendor_id: number;
  vendor_name: string;
  purchase_order_id: number | null;
  po_number: string | null;
  invoice_date: string;
  due_date: string | null;
  subtotal: string;
  tax_amount: string;
  total_amount: string;
  currency: string;
  status: SupplierInvoiceStatus;
  notes: string | null;
  created_at: string;
}

export interface ProcurementDashboard {
  open_purchase_orders: number;
  pending_approvals: number;
  pending_request_approvals: number;
  pending_order_approvals: number;
  goods_received_today: number;
  outstanding_deliveries: number;
  procurement_spend_month: number;
  procurement_spend_total: number;
  po_by_status: Array<{ status: string; total: number }>;
  requests_by_status: Array<{ status: string; total: number }>;
  recent_orders: Array<{ id: number; po_number: string; status: string; total_amount: string; order_date: string; expected_delivery_date: string | null; vendor_name: string }>;
  recent_receipts: Array<{ id: number; receipt_number: string; status: string; received_at: string; po_number: string; vendor_name: string }>;
  monthly_spend: Array<{ month: string; amount: number }>;
}

export interface PurchaseRequestFormValues {
  requester_user_id?: number;
  department?: string;
  priority: Priority;
  required_date?: string;
  notes?: string;
  items: Array<{ product_id: number; description?: string; quantity: number; estimated_unit_cost: number; notes?: string }>;
}

export interface PurchaseOrderFormValues {
  vendor_id: number;
  warehouse_id?: number | null;
  purchase_request_id?: number | null;
  currency: string;
  tax_rate: number;
  discount_amount: number;
  order_date?: string;
  expected_delivery_date?: string;
  notes?: string;
  items: Array<{ product_id: number; description?: string; quantity_ordered: number; unit_price: number; notes?: string }>;
}

export interface GoodsReceiptFormValues {
  purchase_order_id: number;
  warehouse_id?: number | null;
  notes?: string;
  items: Array<{
    purchase_order_item_id: number;
    product_id: number;
    quantity_accepted: number;
    quantity_damaged: number;
    quantity_short: number;
    warehouse_location_id?: number | null;
    condition?: string;
    notes?: string;
  }>;
}

export interface SupplierInvoiceFormValues {
  invoice_number: string;
  vendor_id: number;
  purchase_order_id?: number | null;
  invoice_date: string;
  due_date?: string;
  subtotal: number;
  tax_amount: number;
  total_amount: number;
  currency: string;
  notes?: string;
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
