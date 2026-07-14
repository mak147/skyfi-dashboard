import { apiClient } from '@/lib/apiClient';
import type { 
  Connection, 
  ConnectionFilters, 
  ConnectionFormData, 
  ConnectionListResponse 
} from '../types';

export const getConnections = async (
  page: number,
  perPage: number,
  filters: ConnectionFilters,
  sort: string
): Promise<ConnectionListResponse> => {
  const params = new URLSearchParams();
  params.set('page[number]', String(page));
  params.set('page[size]', String(perPage));
  params.set('sort', sort);

  if (filters.status) params.set('filter[status]', filters.status);
  if (filters.type) params.set('filter[type]', filters.type);
  if (filters.customer_id) params.set('filter[customer_id]', String(filters.customer_id));
  if (filters.package_id) params.set('filter[package_id]', String(filters.package_id));
  if (filters.search) params.set('filter[search]', filters.search);

  const response = await apiClient.get<ConnectionListResponse>(`/connections?${params.toString()}`);
  return response.data;
};

export const getConnection = async (id: number): Promise<Connection> => {
  const response = await apiClient.get<{ data: { attributes: Connection } }>(`/connections/${id}`);
  return response.data.data.attributes;
};

export const createConnection = async (data: ConnectionFormData): Promise<Connection> => {
  const response = await apiClient.post<{ data: { attributes: Connection } }>('/connections', data);
  return response.data.data.attributes;
};

export const updateConnection = async (id: number, data: ConnectionFormData): Promise<Connection> => {
  const response = await apiClient.put<{ data: { attributes: Connection } }>(`/connections/${id}`, data);
  return response.data.data.attributes;
};

export const deleteConnection = async (id: number): Promise<void> => {
  await apiClient.delete(`/connections/${id}`);
};

export const activateConnection = async (id: number): Promise<Connection> => {
  const response = await apiClient.patch<{ data: { attributes: Connection } }>(`/connections/${id}/activate`);
  return response.data.data.attributes;
};

export const suspendConnection = async (id: number): Promise<Connection> => {
  const response = await apiClient.patch<{ data: { attributes: Connection } }>(`/connections/${id}/suspend`);
  return response.data.data.attributes;
};

export const disconnectConnection = async (id: number): Promise<Connection> => {
  const response = await apiClient.patch<{ data: { attributes: Connection } }>(`/connections/${id}/disconnect`);
  return response.data.data.attributes;
};

export const transferConnection = async (id: number, customerId: number): Promise<Connection> => {
  const response = await apiClient.patch<{ data: { attributes: Connection } }>(`/connections/${id}/transfer`, { customer_id: customerId });
  return response.data.data.attributes;
};
