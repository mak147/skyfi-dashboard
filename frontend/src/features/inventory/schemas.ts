import { z } from 'zod';

const numberField = z.coerce.number().int().positive();
const optionalNumber = z.preprocess((value) => (value === '' || value === undefined ? undefined : value), z.coerce.number().int().positive().optional());

export const productSchema = z.object({
  category_id: numberField,
  model_id: optionalNumber,
  unit_id: numberField,
  sku: z.string().trim().min(1).max(80).regex(/^[A-Za-z0-9._-]+$/, 'Use letters, numbers, dots, underscores, or hyphens.'),
  name: z.string().trim().min(1).max(200),
  description: z.string().max(10000).optional(),
  barcode: z.string().max(100).optional(),
  qr_code_value: z.string().max(255).optional(),
  tracking_mode: z.enum(['quantity', 'serialized']),
  standard_cost: z.coerce.number().min(0),
  minimum_stock: z.coerce.number().min(0),
  reorder_level: z.coerce.number().min(0),
  status: z.enum(['active', 'inactive', 'discontinued']),
});

export const assetSchema = z.object({
  product_id: numberField,
  vendor_id: optionalNumber,
  network_device_id: optionalNumber,
  asset_tag: z.string().trim().min(1).max(80).regex(/^[A-Za-z0-9._-]+$/),
  serial_number: z.string().trim().min(1).max(150),
  mac_address: z.string().trim().regex(/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/, 'Enter a valid MAC address.').or(z.literal('')).optional(),
  imei: z.string().max(32).optional(),
  barcode: z.string().max(100).optional(),
  purchase_date: z.string().optional(),
  acquisition_cost: z.coerce.number().min(0),
  warranty_starts_at: z.string().optional(),
  warranty_expires_at: z.string().optional(),
  status: z.enum(['in_stock', 'reserved', 'in_transit', 'assigned', 'deployed', 'under_repair', 'returned', 'damaged', 'lost', 'scrapped', 'retired']),
  notes: z.string().max(10000).optional(),
  warehouse_location_id: optionalNumber,
}).superRefine((data, context) => {
  if (data.warranty_starts_at && data.warranty_expires_at && data.warranty_expires_at < data.warranty_starts_at) {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ['warranty_expires_at'], message: 'Warranty expiry cannot precede its start date.' });
  }
});

export const warehouseSchema = z.object({
  code: z.string().trim().min(1).max(50).regex(/^[A-Za-z0-9_-]+$/),
  name: z.string().trim().min(1).max(150),
  type: z.enum(['main', 'branch', 'technician_vehicle', 'repair_depot', 'site_store', 'other']),
  status: z.enum(['active', 'inactive', 'maintenance', 'closed']),
  manager_user_id: optionalNumber,
  address: z.string().max(500).optional(),
  city: z.string().max(100).optional(),
  region: z.string().max(100).optional(),
  notes: z.string().max(10000).optional(),
});

export const transferSchema = z.object({
  source_warehouse_id: numberField,
  destination_warehouse_id: numberField,
  expected_at: z.string().optional(),
  notes: z.string().max(10000).optional(),
  lines: z.array(z.object({
    product_id: numberField,
    source_location_id: numberField,
    destination_location_id: numberField,
    quantity_requested: z.coerce.number().positive(),
    asset_ids: z.array(z.coerce.number().int().positive()).optional(),
  })).min(1, 'Add at least one product.'),
}).refine((data) => data.source_warehouse_id !== data.destination_warehouse_id, {
  path: ['destination_warehouse_id'],
  message: 'Destination must differ from source.',
});

export const stockOperationSchema = z.object({
  reference_type: z.string().max(60).optional(),
  reference_number: z.string().max(100).optional(),
  vendor_id: optionalNumber,
  support_ticket_id: optionalNumber,
  reason: z.string().max(500).optional(),
  notes: z.string().max(10000).optional(),
  occurred_at: z.string().optional(),
  lines: z.array(z.object({
    product_id: numberField,
    quantity: z.coerce.number().positive(),
    source_location_id: optionalNumber,
    destination_location_id: optionalNumber,
    source_condition: z.enum(['available', 'reserved', 'quarantine', 'damaged']).optional(),
    destination_condition: z.enum(['available', 'reserved', 'quarantine', 'damaged']).optional(),
    unit_cost: z.coerce.number().min(0).optional(),
    asset_ids: z.array(z.coerce.number().int().positive()).optional(),
  })).min(1),
});

export type ProductSchemaValues = z.infer<typeof productSchema>;
export type AssetSchemaValues = z.infer<typeof assetSchema>;
export type WarehouseSchemaValues = z.infer<typeof warehouseSchema>;
export type TransferSchemaValues = z.infer<typeof transferSchema>;
export type StockOperationSchemaValues = z.infer<typeof stockOperationSchema>;
