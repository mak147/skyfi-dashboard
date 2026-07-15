import { z } from 'zod';

export const vendorSchema = z.object({
  code: z.string().min(1, 'Supplier code is required.'),
  name: z.string().min(1, 'Company name is required.'),
  status: z.enum(['active', 'inactive', 'on_hold']),
  contact_name: z.string().optional(),
  email: z.string().email('Invalid email address format.').optional().or(z.literal('')),
  phone: z.string().optional(),
  website: z.string().optional(),
  tax_id: z.string().optional(),
  registration_number: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
  country: z.string().min(1, 'Country is required.'),
  payment_terms: z.string().optional(),
  currency: z.string().min(2, 'Currency code is required.').max(3),
  category: z.string().min(1, 'Category is required.'),
  notes: z.string().optional(),
});

export const vendorContactSchema = z.object({
  vendor_id: z.number().optional(),
  first_name: z.string().min(1, 'First name is required.'),
  last_name: z.string().min(1, 'Last name is required.'),
  email: z.string().email('Invalid email address format.').optional().or(z.literal('')),
  phone: z.string().optional(),
  department: z.string().optional(),
  position: z.string().optional(),
  is_primary: z.boolean(),
  is_emergency: z.boolean(),
  notes: z.string().optional(),
});

export const vendorContractSchema = z.object({
  vendor_id: z.number().optional(),
  contract_number: z.string().min(1, 'Contract number is required.'),
  title: z.string().min(1, 'Contract title is required.'),
  start_date: z.string().min(1, 'Start date is required.'),
  end_date: z.string().min(1, 'End date is required.'),
  renewal_date: z.string().optional(),
  contract_value: z.coerce.number().min(0, 'Value cannot be negative.'),
  currency: z.string().min(2, 'Currency code is required.').max(3),
  status: z.enum(['draft', 'active', 'expiring', 'expired', 'terminated']),
  attachment_path: z.string().optional(),
  notes: z.string().optional(),
});

export const vendorQuotationItemSchema = z.object({
  product_id: z.coerce.number().optional().nullable(),
  description: z.string().min(1, 'Item description is required.'),
  quantity: z.coerce.number().min(0.01, 'Quantity must be positive.'),
  unit_price: z.coerce.number().min(0, 'Unit price cannot be negative.'),
  notes: z.string().optional(),
});

export const vendorQuotationSchema = z.object({
  vendor_id: z.number().optional(),
  purchase_request_id: z.coerce.number().optional().nullable(),
  rfq_number: z.string().optional(),
  quotation_number: z.string().min(1, 'Quotation number is required.'),
  quotation_date: z.string().min(1, 'Quotation date is required.'),
  validity_date: z.string().min(1, 'Validity date is required.'),
  total_amount: z.coerce.number().min(0, 'Total cannot be negative.'),
  currency: z.string().min(2, 'Currency code is required.').max(3),
  status: z.enum(['received', 'under_review', 'accepted', 'rejected', 'expired']),
  notes: z.string().optional(),
  items: z.array(vendorQuotationItemSchema).min(1, 'At least one line item is required.'),
});

export const vendorRatingSchema = z.object({
  vendor_id: z.number().min(1, 'Supplier is required.'),
  evaluation_date: z.string().min(1, 'Evaluation date is required.'),
  delivery_performance: z.coerce.number().min(0).max(100),
  order_completion: z.coerce.number().min(0).max(100),
  product_quality: z.coerce.number().min(0).max(100),
  return_rate: z.coerce.number().min(0).max(100),
  average_lead_time_days: z.coerce.number().min(0),
  comments: z.string().optional(),
});
