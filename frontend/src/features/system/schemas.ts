import { z } from 'zod';
export const companySettingsSchema = z.object({ company_name: z.string().min(1), email: z.string().email().optional().or(z.literal('')) });
export const branchSchema = z.object({ code: z.string().min(1), name: z.string().min(1), status: z.enum(['active', 'inactive']).default('active') });
export const departmentSchema = branchSchema.extend({ branch_id: z.number().optional().nullable() });
