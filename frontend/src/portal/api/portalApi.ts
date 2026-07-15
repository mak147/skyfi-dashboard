import { apiClient } from '@/lib/apiClient';

import type {
  NotificationPreferences,
  PaginatedResponse,
  PortalBalance,
  PortalConnection,
  PortalCustomer,
  PortalDashboard,
  PortalNotification,
  PortalPasswordForm,
  PortalProfileForm,
  PortalReplyForm,
  PortalTicketForm,
} from '../types';

interface ResourceResponse<T> {
  data: {
    type: string;
    id: string;
    attributes: T;
  };
}

const unwrap = <T>(response: ResourceResponse<T>): T => response.data.attributes;

export const getDashboard = async (): Promise<PortalDashboard> => {
  const response = await apiClient.get<ResourceResponse<PortalDashboard>>('/portal/dashboard');
  return unwrap(response.data);
};

export const getConnection = async (): Promise<PortalConnection> => {
  const response = await apiClient.get<ResourceResponse<PortalConnection>>('/portal/connection');
  return unwrap(response.data);
};

export const getInvoices = async (page = 1, perPage = 15): Promise<PaginatedResponse<Record<string, unknown>>> => {
  const response = await apiClient.get<PaginatedResponse<Record<string, unknown>>>(
    `/portal/invoices?page[number]=${page}&page[size]=${perPage}`,
  );
  return response.data;
};

export const getInvoice = async (id: number): Promise<Record<string, unknown>> => {
  const response = await apiClient.get<ResourceResponse<Record<string, unknown>>>(`/portal/invoices/${id}`);
  return unwrap(response.data);
};

export const getInvoicePdf = async (id: number): Promise<Record<string, unknown>> => {
  const response = await apiClient.get<ResourceResponse<Record<string, unknown>>>(`/portal/invoices/${id}/pdf`);
  return unwrap(response.data);
};

export const getBalance = async (): Promise<PortalBalance> => {
  const response = await apiClient.get<ResourceResponse<PortalBalance>>('/portal/balance');
  return unwrap(response.data);
};

export const getPayments = async (page = 1, perPage = 15): Promise<PaginatedResponse<Record<string, unknown>>> => {
  const response = await apiClient.get<PaginatedResponse<Record<string, unknown>>>(
    `/portal/payments?page[number]=${page}&page[size]=${perPage}`,
  );
  return response.data;
};

export const getPayment = async (id: number): Promise<Record<string, unknown>> => {
  const response = await apiClient.get<ResourceResponse<Record<string, unknown>>>(`/portal/payments/${id}`);
  return unwrap(response.data);
};

export const getPaymentReceipt = async (id: number): Promise<Record<string, unknown>> => {
  const response = await apiClient.get<ResourceResponse<Record<string, unknown>>>(`/portal/payments/${id}/receipt`);
  return unwrap(response.data);
};

export const getTickets = async (page = 1, perPage = 15): Promise<PaginatedResponse<Record<string, unknown>>> => {
  const response = await apiClient.get<PaginatedResponse<Record<string, unknown>>>(
    `/portal/tickets?page[number]=${page}&page[size]=${perPage}`,
  );
  return response.data;
};

export const getTicket = async (id: number): Promise<Record<string, unknown>> => {
  const response = await apiClient.get<ResourceResponse<Record<string, unknown>>>(`/portal/tickets/${id}`);
  return unwrap(response.data);
};

export const createTicket = async (data: PortalTicketForm): Promise<Record<string, unknown>> => {
  const response = await apiClient.post<ResourceResponse<Record<string, unknown>>>('/portal/tickets', data);
  return unwrap(response.data);
};

export const replyTicket = async (id: number, data: PortalReplyForm): Promise<Record<string, unknown>> => {
  const response = await apiClient.post<ResourceResponse<Record<string, unknown>>>(`/portal/tickets/${id}/reply`, data);
  return unwrap(response.data);
};

export const requestTicketClosure = async (id: number): Promise<Record<string, unknown>> => {
  const response = await apiClient.post<ResourceResponse<Record<string, unknown>>>(`/portal/tickets/${id}/close-request`);
  return unwrap(response.data);
};

export const getNotifications = async (page = 1, perPage = 20): Promise<PaginatedResponse<PortalNotification>> => {
  const response = await apiClient.get<PaginatedResponse<PortalNotification>>(
    `/portal/notifications?page[number]=${page}&page[size]=${perPage}`,
  );
  return response.data;
};

export const markNotificationRead = async (id: number): Promise<PortalNotification> => {
  const response = await apiClient.patch<ResourceResponse<PortalNotification>>(`/portal/notifications/${id}/read`);
  return unwrap(response.data);
};

export const markAllNotificationsRead = async (): Promise<{ marked_read: number }> => {
  const response = await apiClient.patch<ResourceResponse<{ marked_read: number }>>('/portal/notifications/read-all');
  return unwrap(response.data);
};

export const archiveNotification = async (id: number): Promise<PortalNotification> => {
  const response = await apiClient.patch<ResourceResponse<PortalNotification>>(`/portal/notifications/${id}/archive`);
  return unwrap(response.data);
};

export const getPreferences = async (): Promise<NotificationPreferences> => {
  const response = await apiClient.get<ResourceResponse<NotificationPreferences>>('/portal/preferences');
  return unwrap(response.data);
};

export const updatePreferences = async (data: NotificationPreferences): Promise<NotificationPreferences> => {
  const response = await apiClient.put<ResourceResponse<NotificationPreferences>>('/portal/preferences', data);
  return unwrap(response.data);
};

export const getProfile = async (): Promise<PortalCustomer> => {
  const response = await apiClient.get<ResourceResponse<PortalCustomer>>('/portal/profile');
  return unwrap(response.data);
};

export const updateProfile = async (data: PortalProfileForm): Promise<PortalCustomer> => {
  const response = await apiClient.put<ResourceResponse<PortalCustomer>>('/portal/profile', data);
  return unwrap(response.data);
};

export const changePassword = async (data: PortalPasswordForm): Promise<Record<string, unknown>> => {
  const response = await apiClient.post<ResourceResponse<Record<string, unknown>>>('/auth/change-password', {
    current_password: data.current_password,
    new_password: data.new_password,
  });
  return unwrap(response.data);
};

export const forgotPassword = async (email: string): Promise<Record<string, unknown>> => {
  const response = await apiClient.post<ResourceResponse<Record<string, unknown>>>('/auth/forgot-password', { email });
  return unwrap(response.data);
};

export const resetPassword = async (token: string, password: string): Promise<Record<string, unknown>> => {
  const response = await apiClient.post<ResourceResponse<Record<string, unknown>>>('/auth/reset-password', {
    token,
    password,
  });
  return unwrap(response.data);
};
