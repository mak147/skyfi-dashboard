import type { Invoice } from '@/features/billing/types';
import type { Payment } from '@/features/payments/types';
import type { SupportTicket } from '@/features/support/types';

export interface PortalCustomer {
  id: number;
  customer_code: string;
  full_name: string;
  phone: string;
  whatsapp: string | null;
  email: string | null;
  address: string;
  city: string;
  area: string;
  status: string;
  registration_date: string | null;
  installation_date: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
}

export interface PortalDashboard {
  customer: PortalCustomer;
  connection: Record<string, unknown> | null;
  package: Record<string, unknown> | null;
  latest_invoice: Invoice | null;
  recent_payments: Payment[];
  active_tickets: SupportTicket[];
  recent_notifications: PortalNotification[];
  outstanding_balance: number;
  is_online: boolean;
}

export interface PortalConnection {
  connection: Record<string, unknown>;
  package: Record<string, unknown> | null;
  router: Record<string, unknown> | null;
  monthly_usage: {
    status: string;
    message: string;
  };
}

export interface PortalNotification {
  id: number;
  title: string;
  body: string;
  status: 'read' | 'unread' | 'archived';
  severity: string;
  created_at: string;
  action_url?: string | null;
}

export interface NotificationPreferences {
  user_id: number;
  preferences: Array<{
    channel: string;
    category: string;
    is_enabled: number;
    quiet_hours_start: string | null;
    quiet_hours_end: string | null;
    quiet_hours_timezone: string | null;
  }>;
  categories: string[];
  channels: string[];
}

export interface PortalTicketForm {
  category_id: number;
  priority: 'low' | 'normal' | 'high' | 'urgent';
  subject: string;
  description: string;
  connection_id?: number | null;
}

export interface PortalReplyForm {
  body: string;
}

export interface PortalProfileForm {
  full_name: string;
  phone: string;
  whatsapp: string;
  email: string;
  address: string;
  city: string;
  area: string;
  emergency_contact_name: string;
  emergency_contact_phone: string;
}

export interface PortalPasswordForm {
  current_password: string;
  new_password: string;
  confirm_password: string;
}

export interface PortalBalance {
  outstanding_balance: number;
  currency: string;
}

export interface PaginatedResponse<T> {
  data: Array<{
    type: string;
    id: string;
    attributes: T;
  }>;
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}
