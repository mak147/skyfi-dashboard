import { z } from 'zod';

const conditionRuleSchema: z.ZodType<unknown> = z.lazy(() =>
  z.union([
    z.object({
      logic: z.enum(['AND', 'OR']),
      rules: z.array(conditionRuleSchema),
    }),
    z.object({
      field: z.string().min(1, 'Field is required'),
      operator: z.string().min(1, 'Operator is required'),
      value: z.unknown().optional(),
    }),
  ]),
);

export const workflowFormSchema = z.object({
  name: z.string().min(2, 'Name is required'),
  description: z.string().optional().default(''),
  status: z.enum(['draft', 'active', 'paused', 'disabled']),
  is_enabled: z.boolean(),
  schedule_mode: z.enum(['immediate', 'delayed', 'cron', 'recurring']),
  cron_expression: z.string().optional().default(''),
  delay_seconds: z.coerce.number().int().min(0),
  max_retries: z.coerce.number().int().min(0).max(10),
  retry_delay_seconds: z.coerce.number().int().min(0),
  changelog: z.string().optional(),
  definition: z.object({
    trigger: z.object({
      event_key: z.string().min(1, 'Trigger event is required'),
      source_module: z.string().optional(),
      filter: z.record(z.unknown()).nullable().optional(),
    }),
    conditions: conditionRuleSchema,
    actions: z
      .array(
        z.object({
          type: z.string().min(1),
          name: z.string().nullable().optional(),
          config: z.record(z.unknown()).optional(),
          order: z.number().optional(),
          continue_on_failure: z.boolean().optional(),
          is_enabled: z.boolean().optional(),
        }),
      )
      .min(1, 'At least one action is required'),
    schedule: z
      .object({
        mode: z.enum(['immediate', 'delayed', 'cron', 'recurring']).optional(),
        delay_seconds: z.number().optional(),
        cron: z.string().nullable().optional(),
      })
      .optional(),
  }),
});

export type WorkflowFormSchema = z.infer<typeof workflowFormSchema>;
