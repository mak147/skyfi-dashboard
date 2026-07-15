export type TrackingMode = 'quantity' | 'serialized';
export type ProductStatus = 'active' | 'inactive' | 'discontinued';
export type AssetStatus =
  | 'in_stock'
  | 'reserved'
  | 'in_transit'
  | 'assigned'
  | 'deployed'
  | 'under_repair'
  | 'returned'
  | 'damaged'
  | 'lost'
  | 'scrapped'
  | 'retired';
export type WarehouseStatus = 'active' | 'inactive' | 'maintenance' | 'closed';
export type WarehouseType = 'main' | 'branch' | 'technician_vehicle' | 'repair_depot' | 'site_store' | 'other';
export type TransferStatus = 'draft' | 'pending' | 'approved' | 'in_transit' | 'partially_received' | 'completed' | 'cancelled';
export type StockMovementType = 'opening_balance' | 'stock_in' | 'stock_out' | 'transfer_dispatch' | 'transfer_receipt' | 'adjustment_in' | 'adjustment_out' | 'return' | 'damaged' | 'scrap' | 'reversal';

export interface CatalogItem {
  id: number;
  code?: string;
  name: string;
  status: string;
  parent_id?: number | null;
  parent_name?: string | null;
  brand_id?: number;
  brand_name?: string;
  model_number?: string | null;
  symbol?: string;
  decimal_places?: number;
  contact_name?: string | null;
  email?: string | null;
  phone?: string | null;
}

export interface Product {
  id: number;
  category_id: number;
  category_name: string;
  model_id: number | null;
  model_name: string | null;
  brand_id: number | null;
  brand_name: string | null;
  unit_id: number;
  unit_name: string;
  unit_symbol: string;
  sku: string;
  name: string;
  description: string | null;
  barcode: string | null;
  qr_code_value: string | null;
  tracking_mode: TrackingMode;
  standard_cost: string;
  minimum_stock: string;
  reorder_level: string;
  total_stock: string;
  status: ProductStatus;
  vendors?: Array<{ vendor_id: number; vendor_name: string; vendor_sku: string | null; is_default: boolean }>;
  created_at: string;
  updated_at: string;
}

export interface WarehouseLocation {
  id: number;
  warehouse_id: number;
  parent_id: number | null;
  code: string;
  name: string;
  description: string | null;
  status: 'active' | 'inactive';
  quantity_stock: string;
  stock_value: string;
  serialized_assets: number;
}

export interface Warehouse {
  id: number;
  code: string;
  name: string;
  type: WarehouseType;
  status: WarehouseStatus;
  manager_user_id: number | null;
  manager_name: string | null;
  address: string | null;
  city: string | null;
  region: string | null;
  notes: string | null;
  location_count: number;
  quantity_stock: string;
  stock_value: string;
  serialized_assets: number;
  locations?: WarehouseLocation[];
  created_at: string;
}

export interface Asset {
  id: number;
  product_id: number;
  sku: string;
  product_name: string;
  category_name: string;
  model_name: string | null;
  brand_name: string | null;
  vendor_id: number | null;
  vendor_name: string | null;
  network_device_id: number | null;
  network_device_name: string | null;
  asset_tag: string;
  serial_number: string;
  mac_address: string | null;
  imei: string | null;
  barcode: string | null;
  qr_code_value: string | null;
  purchase_date: string | null;
  acquisition_cost: string;
  warranty_starts_at: string | null;
  warranty_expires_at: string | null;
  status: AssetStatus;
  notes: string | null;
  current_assignment_id: number | null;
  assignment_type: string | null;
  warehouse_id: number | null;
  warehouse_name: string | null;
  warehouse_location_id: number | null;
  warehouse_location_name: string | null;
  customer_id: number | null;
  customer_name: string | null;
  tower_id: number | null;
  tower_name: string | null;
  pop_site_id: number | null;
  pop_site_name: string | null;
  technician_id: number | null;
  technician_name: string | null;
  created_at: string;
}

export interface AssetTimelineItem {
  id: number | string;
  type: string;
  description: string;
  old_status: string | null;
  new_status: string | null;
  occurred_at: string;
  actor_name: string | null;
  metadata: Record<string, unknown>;
}

export interface StockBalance {
  product_id: number;
  sku: string;
  product_name: string;
  unit_symbol: string;
  warehouse_id: number;
  warehouse_name: string;
  warehouse_location_id: number;
  location_code: string;
  location_name: string;
  stock_condition: string;
  quantity: string;
  average_unit_cost: string;
  stock_value: string;
  is_low_stock: boolean;
}

export interface StockMovementLine {
  id: number;
  product_id: number;
  product_name: string;
  sku: string;
  asset_id: number | null;
  asset_tag: string | null;
  source_warehouse_name: string | null;
  source_location_code: string | null;
  destination_warehouse_name: string | null;
  destination_location_code: string | null;
  quantity: string;
  unit_cost: string;
  total_cost: string;
}

export interface StockMovement {
  id: number;
  movement_number: string;
  movement_type: StockMovementType;
  status: 'posted' | 'reversed';
  reference_type: string | null;
  reference_number: string | null;
  reason: string | null;
  occurred_at: string;
  posted_by_name: string;
  line_count: number;
  total_quantity: string;
  total_value: string;
  lines?: StockMovementLine[];
}

export interface TransferLine {
  id: number;
  product_id: number;
  product_name: string;
  sku: string;
  tracking_mode: TrackingMode;
  source_location_id: number;
  source_location_name: string;
  destination_location_id: number;
  destination_location_name: string;
  quantity_requested: string;
  quantity_dispatched: string;
  quantity_received: string;
  unit_cost: string;
  assets: Array<{ id: number; asset_tag: string; serial_number: string; dispatched_at: string | null; received_at: string | null }>;
}

export interface WarehouseTransfer {
  id: number;
  transfer_number: string;
  source_warehouse_id: number;
  source_warehouse_name: string;
  destination_warehouse_id: number;
  destination_warehouse_name: string;
  status: TransferStatus;
  requested_by_name: string;
  requested_at: string;
  expected_at: string | null;
  notes: string | null;
  line_count: number;
  total_requested: string;
  total_dispatched: string;
  total_received: string;
  lines?: TransferLine[];
}

export interface InventoryDashboard {
  total_products: number;
  total_assets: number;
  active_warehouses: number;
  stock_value: string;
  serialized_asset_value: string;
  low_stock_products: number;
  damaged_quantity: string;
  damaged_assets: number;
  pending_transfers: number;
  asset_statuses: Array<{ status: string; total: number }>;
  warehouse_stock: Array<{ id: number; name: string; stock_value: string }>;
  recent_movements: StockMovement[];
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

export interface ProductFormValues {
  category_id: number;
  model_id?: number | null;
  unit_id: number;
  sku: string;
  name: string;
  description?: string;
  barcode?: string;
  qr_code_value?: string;
  tracking_mode: TrackingMode;
  standard_cost: number;
  minimum_stock: number;
  reorder_level: number;
  status: ProductStatus;
}

export interface AssetFormValues {
  product_id: number;
  vendor_id?: number | null;
  network_device_id?: number | null;
  asset_tag: string;
  serial_number: string;
  mac_address?: string;
  imei?: string;
  barcode?: string;
  purchase_date?: string;
  acquisition_cost: number;
  warranty_starts_at?: string;
  warranty_expires_at?: string;
  status: AssetStatus;
  notes?: string;
  initial_assignment?: { assignment_type: 'warehouse'; warehouse_location_id: number };
}

export interface WarehouseFormValues {
  code: string;
  name: string;
  type: WarehouseType;
  status: WarehouseStatus;
  manager_user_id?: number | null;
  address?: string;
  city?: string;
  region?: string;
  notes?: string;
}

export interface TransferFormValues {
  source_warehouse_id: number;
  destination_warehouse_id: number;
  expected_at?: string;
  notes?: string;
  lines: Array<{ product_id: number; source_location_id: number; destination_location_id: number; quantity_requested: number; asset_ids?: number[] }>;
}
