import { z } from 'zod';

export const createApiKeySchema = z.object({
  name: z.string().min(1, 'Name is required').max(180),
  client_application_id: z.number().nullable().optional(),
  scopes: z.array(z.string()).min(1, 'At least one scope is required'),
  ip_allow_list: z.array(z.string()).nullable().optional(),
  rate_limit_per_minute: z.number().min(1).nullable().optional(),
  expires_at: z.string().nullable().optional(),
});

export const updateApiKeySchema = z.object({
  name: z.string().min(1).max(180).optional(),
  scopes: z.array(z.string()).min(1).optional(),
  ip_allow_list: z.array(z.string()).nullable().optional(),
  is_active: z.boolean().optional(),
  rate_limit_per_minute: z.number().min(1).nullable().optional(),
  expires_at: z.string().nullable().optional(),
});

export const createApplicationSchema = z.object({
  name: z.string().min(1, 'Name is required').max(180),
  description: z.string().nullable().optional(),
  redirect_uris: z.array(z.string()).nullable().optional(),
  rate_limit_per_minute: z.number().min(1).default(60),
});

export const createWebhookSchema = z.object({
  name: z.string().min(1, 'Name is required').max(180),
  url: z.string().url('Must be a valid URL'),
  client_application_id: z.number().nullable().optional(),
  events: z.array(z.string()).min(1, 'At least one event is required'),
  is_active: z.boolean().optional(),
  is_inbound: z.boolean().optional(),
  retry_policy: z.object({ max_attempts: z.number().min(1).max(10), backoff: z.enum(['exponential', 'linear']) }).optional(),
  filter_rules: z.record(z.unknown()).nullable().optional(),
  content_type: z.string().optional(),
});

export const updateWebhookSchema = z.object({
  name: z.string().min(1).max(180).optional(),
  url: z.string().url().optional(),
  events: z.array(z.string()).min(1).optional(),
  is_active: z.boolean().optional(),
  retry_policy: z.object({ max_attempts: z.number().min(1).max(10), backoff: z.enum(['exponential', 'linear']) }).optional(),
  filter_rules: z.record(z.unknown()).nullable().optional(),
  content_type: z.string().optional(),
});

export const updateConnectorSchema = z.object({
  name: z.string().min(1).max(180).optional(),
  description: z.string().nullable().optional(),
  config: z.record(z.unknown()).optional(),
  is_enabled: z.boolean().optional(),
  rate_limit_per_minute: z.number().min(1).nullable().optional(),
});

export type CreateApiKeyForm = z.infer<typeof createApiKeySchema>;
export type CreateApplicationForm = z.infer<typeof createApplicationSchema>;
export type CreateWebhookForm = z.infer<typeof createWebhookSchema>;
export type UpdateWebhookForm = z.infer<typeof updateWebhookSchema>;
export type UpdateConnectorForm = z.infer<typeof updateConnectorSchema>;
