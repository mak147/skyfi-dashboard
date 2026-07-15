import { z } from 'zod';

export const preferenceRowSchema = z.object({
  channel: z.enum(['in_app', 'email', 'sms', 'push', 'webhook']),
  category: z.string().min(1),
  is_enabled: z.union([z.boolean(), z.number()]),
  quiet_hours_start: z.string().nullable().optional(),
  quiet_hours_end: z.string().nullable().optional(),
  quiet_hours_timezone: z.string().nullable().optional(),
});

export const templateSchema = z.object({
  code: z.string().min(1).max(100),
  name: z.string().min(1).max(180),
  category: z.string().min(1),
  channel: z.enum(['in_app', 'email', 'sms', 'push', 'webhook']),
  subject_template: z.string().max(500).optional().nullable(),
  body_template: z.string().min(1),
  locale: z.string().min(2).max(20).default('en'),
  is_transactional: z.union([z.boolean(), z.number()]).optional(),
  is_active: z.union([z.boolean(), z.number()]).optional(),
  variables: z.array(z.string()).optional().nullable(),
});

export type TemplateFormValues = z.infer<typeof templateSchema>;
