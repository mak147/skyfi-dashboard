import { z } from 'zod';

const baseRouterSchema = z.object({
  name: z.string().trim().min(1, 'Router name is required.').max(150),
  host: z.string().trim().min(1, 'Host or IP address is required.').max(253),
  api_port: z.coerce.number().int().min(1).max(65535),
  api_username: z.string().trim().min(1, 'API username is required.').max(128),
  router_group_id: z.coerce.number().int().positive().nullable(),
  tag_ids: z.array(z.number().int().positive()),
  location: z.string().max(255),
  site: z.string().max(150),
  notes: z.string().max(65535),
  is_enabled: z.boolean(),
});

export const createRouterSchema = baseRouterSchema.extend({
  api_password: z.string().min(1, 'API password is required.').max(1024),
});

export const editRouterSchema = baseRouterSchema.extend({
  api_password: z.string().max(1024).optional(),
});

export const connectionTestSchema = z.object({
  host: z.string().trim().min(1, 'Host or IP address is required.').max(253),
  api_port: z.coerce.number().int().min(1).max(65535),
  api_username: z.string().trim().min(1, 'API username is required.').max(128),
  api_password: z.string().min(1, 'API password is required.').max(1024),
});

export type ConnectionTestValues = z.infer<typeof connectionTestSchema>;
