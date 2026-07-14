import { z } from 'zod';

export const hotspotUserFormSchema = z.object({
  username: z
    .string()
    .trim()
    .min(1, 'Username is required.')
    .max(100, 'Username must not exceed 100 characters.')
    .regex(/^[A-Za-z0-9._\-+@]+$/, 'Username contains invalid characters.'),
  password: z.string().min(4, 'Password must be at least 4 characters.').max(255),
  router_id: z.coerce.number().int().positive('Please select a MikroTik router.'),
  profile_name: z.string().trim().min(1, 'Profile name is required.').max(100),
  profile_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  customer_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  connection_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  package_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  limit_uptime: z.string().trim().max(50).optional().or(z.literal('')),
  limit_bytes_total: z.coerce.number().int().positive().optional().or(z.literal(0)),
  mac_address: z.string().trim().max(17).optional().or(z.literal('')),
  status: z.enum(['active', 'disabled', 'suspended', 'pending']).default('active'),
  notes: z.string().max(2000).optional().or(z.literal('')),
});

export type HotspotUserFormValues = z.infer<typeof hotspotUserFormSchema>;

export const editHotspotUserFormSchema = hotspotUserFormSchema.extend({
  password: z.string().min(4, 'Password must be at least 4 characters.').max(255).optional().or(z.literal('')),
});

export type EditHotspotUserFormValues = z.infer<typeof editHotspotUserFormSchema>;

export const hotspotProfileFormSchema = z.object({
  name: z.string().trim().min(1, 'Profile name is required.').max(100),
  router_id: z.coerce.number().int().positive('Please select a router.'),
  router_profile_name: z.string().trim().min(1, 'Router profile name is required.').max(100),
  rate_limit_up: z.string().trim().max(50).optional().or(z.literal('')),
  rate_limit_down: z.string().trim().max(50).optional().or(z.literal('')),
  session_timeout: z.coerce.number().int().positive().optional().or(z.literal(0)),
  idle_timeout: z.coerce.number().int().positive().optional().or(z.literal(0)),
  shared_users: z.coerce.number().int().positive().default(1),
  mac_cookie_timeout: z.string().trim().max(50).optional().or(z.literal('')),
  login_methods: z.string().trim().default('http-pap'),
  status: z.enum(['active', 'inactive']).default('active'),
  notes: z.string().max(2000).optional().or(z.literal('')),
});

export type HotspotProfileFormValues = z.infer<typeof hotspotProfileFormSchema>;

export const generateVoucherBatchSchema = z.object({
  hotspot_profile_id: z.coerce.number().int().positive('Please select a hotspot profile.'),
  router_id: z.coerce.number().int().positive('Please select a router.'),
  quantity: z.coerce.number().int().min(1, 'At least 1 voucher.').max(1000, 'Max 1000 vouchers.'),
  prefix: z.string().trim().max(10).regex(/^[A-Z0-9]*$/, 'Uppercase alphanumeric only.').optional().or(z.literal('')),
  price_per_voucher: z.coerce.number().min(0).optional().or(z.literal(0)),
  time_limit: z.string().trim().max(50).optional().or(z.literal('')),
  data_limit_mb: z.coerce.number().int().positive().optional().or(z.literal(0)),
  validity_days: z.coerce.number().int().positive().optional().or(z.literal(0)),
  notes: z.string().max(2000).optional().or(z.literal('')),
});

export type GenerateVoucherBatchValues = z.infer<typeof generateVoucherBatchSchema>;

export const resetPasswordSchema = z.object({
  password: z.string().min(4, 'Password must be at least 4 characters.').max(255),
});

export type ResetPasswordValues = z.infer<typeof resetPasswordSchema>;

export const importHotspotUsersSchema = z.object({
  router_id: z.coerce.number().int().positive('Please select a router.'),
  usernames: z.array(z.string()).default([]),
  default_customer_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  default_connection_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  default_package_id: z.coerce.number().int().positive().optional().or(z.literal(0)),
  overwrite_conflicts: z.boolean().default(false),
});

export type ImportHotspotUsersValues = z.infer<typeof importHotspotUsersSchema>;
