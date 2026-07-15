import { z } from 'zod';

export const profileSchema = z.object({
  full_name: z.string().trim().min(1, 'Full name is required.'),
  phone: z.string().trim().min(1, 'Phone number is required.'),
  whatsapp: z.string().trim().optional(),
  email: z.union([z.string().email(), z.literal('')]),
  address: z.string().trim().min(1, 'Address is required.'),
  city: z.string().trim().min(1, 'City is required.'),
  area: z.string().trim().min(1, 'Area is required.'),
  emergency_contact_name: z.string().trim().optional(),
  emergency_contact_phone: z.string().trim().optional(),
});

export const passwordSchema = z
  .object({
    current_password: z.string().min(8, 'Current password is required.'),
    new_password: z.string().min(8, 'New password must be at least 8 characters.'),
    confirm_password: z.string().min(8, 'Please confirm your new password.'),
  })
  .refine((data) => data.new_password === data.confirm_password, {
    message: 'Passwords do not match.',
    path: ['confirm_password'],
  });

export const ticketSchema = z.object({
  category_id: z.number({ invalid_type_error: 'Category is required.' }).min(1, 'Category is required.'),
  priority: z.enum(['low', 'normal', 'high', 'urgent']),
  subject: z.string().trim().min(1, 'Subject is required.'),
  description: z.string().trim().min(1, 'Description is required.'),
  connection_id: z.number().nullable().optional(),
});

export const replySchema = z.object({
  body: z.string().trim().min(1, 'Reply body is required.'),
});

export const forgotPasswordSchema = z.object({
  email: z.string().trim().email('Please enter a valid email address.'),
});

export const resetPasswordSchema = z
  .object({
    token: z.string().trim().min(1, 'Reset token is required.'),
    password: z.string().min(8, 'Password must be at least 8 characters.'),
    confirm_password: z.string().min(8, 'Please confirm your password.'),
  })
  .refine((data) => data.password === data.confirm_password, {
    message: 'Passwords do not match.',
    path: ['confirm_password'],
  });
