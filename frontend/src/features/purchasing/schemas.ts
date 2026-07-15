import { z } from 'zod';

const numberField = z.coerce.number().int().positive();
const optionalNumberOrNull = z.preprocess((value) => (value === '' || value === undefined || value === null ? undefined : value), z.coerce.number().int().positive().optional());

export const purchaseRequestSchema = z.object({
  department: z.string().max(100).optional(),
  priority: z.enum(['low', 'normal', 'high', 'urgent']),
  required_date: z.string().optional(),
  notes: z.string().max(10000).optional(),
  items: z.array(z.object({
    product_id: numberField,
    description: z.string().max(500).optional(),
    quantity: z.coerce.number().positive('Quantity must be greater than zero.'),
    estimated_unit_cost: z.coerce.number().min(0),
    notes: z.string().max(1000).optional(),
  })).min(1, 'Add at least one item.'),
});

export const purchaseOrderSchema = z.object({
  vendor_id: numberField,
  warehouse_id: optionalNumberOrNull,
  purchase_request_id: optionalNumberOrNull,
  currency: z.string().min(2).max(3),
  tax_rate: z.coerce.number().min(0).max(100),
  discount_amount: z.coerce.number().min(0),
  order_date: z.string().optional(),
  expected_delivery_date: z.string().optional(),
  notes: z.string().max(10000).optional(),
  items: z.array(z.object({
    product_id: numberField,
    description: z.string().max(500).optional(),
    quantity_ordered: z.coerce.number().positive('Quantity must be greater than zero.'),
    unit_price: z.coerce.number().min(0),
    notes: z.string().max(1000).optional(),
  })).min(1, 'Add at least one item.'),
});

export const goodsReceiptSchema = z.object({
  purchase_order_id: numberField,
  warehouse_id: optionalNumberOrNull,
  notes: z.string().max(10000).optional(),
  items: z.array(z.object({
    purchase_order_item_id: numberField,
    product_id: numberField,
    quantity_accepted: z.coerce.number().min(0),
    quantity_damaged: z.coerce.number().min(0),
    quantity_short: z.coerce.number().min(0),
    warehouse_location_id: optionalNumberOrNull,
    condition: z.enum(['available', 'reserved', 'quarantine', 'damaged']).optional(),
    notes: z.string().max(1000).optional(),
  })).min(1, 'Add at least one receipt line.'),
});

export const supplierInvoiceSchema = z.object({
  invoice_number: z.string().trim().min(1).max(80),
  vendor_id: numberField,
  purchase_order_id: optionalNumberOrNull,
  invoice_date: z.string().min(1, 'Invoice date is required.'),
  due_date: z.string().optional(),
  subtotal: z.coerce.number().min(0),
  tax_amount: z.coerce.number().min(0),
  total_amount: z.coerce.number().min(0),
  currency: z.string().min(2).max(3),
  notes: z.string().max(10000).optional(),
});

export type PurchaseRequestSchemaValues = z.infer<typeof purchaseRequestSchema>;
export type PurchaseOrderSchemaValues = z.infer<typeof purchaseOrderSchema>;
export type GoodsReceiptSchemaValues = z.infer<typeof goodsReceiptSchema>;
export type SupplierInvoiceSchemaValues = z.infer<typeof supplierInvoiceSchema>;
