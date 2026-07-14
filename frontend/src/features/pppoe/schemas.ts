import { z } from 'zod';

export const pppoeFormSchema = z.object({
  username: z
    .string()
    .trim()
    .min(1, 'Username is required.')
    .max(100, 'Username must not exceed 100 characters.')
    .regex(/^[A-Za-z0-9._\-+@]+$/, 'Username contains invalid characters.'),
  password: z.string().min(6, 'Password must be at least 6 characters.').max(255),
  customer_id: z.coerce.number().int().positive('Please select a customer.'),
  connection_id: z.coerce.number().int().positive('Please select a connection.'),
  package_id: z.coerce.number().int().positive('Please select an internet package.'),
  router_id: z.coerce.number().int().positive('Please select a MikroTik router.'),
  profile: z.string().trim().min(1, 'PPPoE profile name is required.').max(100),
  service: z.string().trim().default('pppoe'),
  ip_pool: z.string().trim().max(100).optional().or(z.literal('')),
  static_ip: z.string().trim().max(45).optional().or(z.literal('')),
  mac_binding: z.string().trim().max(17).optional().or(z.literal('')),
  caller_id: z.string().trim().max(100).optional().or(z.literal('')),
  rate_limit: z.string().trim().max(100).optional().or(z.literal('')),
  session_timeout: z.coerce.number().int().positive().optional().or(z.literal('')),
  idle_timeout: z.coerce.number().int().positive().optional().or(z.literal('')),
  shared_users: z.coerce.number().int().positive().default(1),
  status: z.enum(['active', 'disabled', 'suspended', 'pending', 'error']).default('active'),
  notes: z.string().max(2000).optional().or(z.literal('')),
});

export type PppoeFormValues = z.infer<typeof pppoeFormSchema>;

export const editPppoeFormSchema = pppoeFormSchema.extend({
  password: z.string().min(6, 'Password must be at least 6 characters.').max(255).optional().or(z.literal('')),
});

export type EditPppoeFormValues = z.infer<typeof editPppoeFormSchema>;
export type AnyPppoeFormValues = EditPppoeFormValues;

export const resetPasswordSchema = z.object({
  password: z.string().min(6, 'Password must be at least 6 characters.').max(255),
});

export type ResetPasswordValues = z.infer<typeof resetPasswordSchema>;

export const changePackageSchema = z.object({
  package_id: z.coerce.number().int().positive('Please select a new package.'),
  profile: z.string().trim().max(100).optional().or(z.literal('')),
});

export type ChangePackageValues = z.infer<typeof changePackageSchema>;

export const importUsersSchema = z.object({
  router_id: z.coerce.number().int().positive('Please select a router.'),
  usernames: z.array(z.string()).default([]),
  default_customer_id: z.coerce.number().int().positive().optional().or(z.literal('')),
  default_connection_id: z.coerce.number().int().positive().optional().or(z.literal('')),
  default_package_id: z.coerce.number().int().positive().optional().or(z.literal('')),
  overwrite_conflicts: z.boolean().default(false),
});

export type ImportUsersValues = z.infer<typeof importUsersSchema>;
