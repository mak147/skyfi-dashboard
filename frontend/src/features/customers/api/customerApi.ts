import { apiClient } from '@/lib/apiClient';

import type {
  Customer,
  CustomerFilters,
  CustomerFormData,
  CustomerListResponse,
} from '../types';

interface SingleResponse {
  data: {
    type: 'customers';
    id: string;
    attributes: Customer;
  };
}

export const getCustomers = async (
  page: number,
  perPage: number,
  filters: CustomerFilters,
  sort: string,
): Promise<CustomerListResponse> => {
  const params = new URLSearchParams();
  params.set('page[number]', String(page));
  params.set('page[size]', String(perPage));
  params.set('sort', sort);

  if (filters.status) params.set('filter[status]', filters.status);
  if (filters.city) params.set('filter[city]', filters.city);
  if (filters.area) params.set('filter[area]', filters.area);
  if (filters.search) params.set('filter[search]', filters.search);

  const response = await apiClient.get<CustomerListResponse>(`/customers?${params.toString()}`);
  return response.data;
};

export const getCustomer = async (id: number): Promise<Customer> => {
  const response = await apiClient.get<SingleResponse>(`/customers/${id}`);
  return response.data.data.attributes;
};

export const createCustomer = async (data: CustomerFormData): Promise<Customer> => {
  const payload = {
    ...data,
    installation_technician_id: data.installation_technician_id ? Number(data.installation_technician_id) : null,
  };
  const response = await apiClient.post<SingleResponse>('/customers', payload);
  return response.data.data.attributes;
};

export const updateCustomer = async (id: number, data: CustomerFormData): Promise<Customer> => {
  const payload = {
    ...data,
    installation_technician_id: data.installation_technician_id ? Number(data.installation_technician_id) : null,
  };
  const response = await apiClient.put<SingleResponse>(`/customers/${id}`, payload);
  return response.data.data.attributes;
};

export const deleteCustomer = async (id: number): Promise<void> => {
  await apiClient.delete(`/customers/${id}`);
};

export const changeCustomerStatus = async (id: number, status: string): Promise<Customer> => {
  const response = await apiClient.patch<SingleResponse>(`/customers/${id}/status`, { status });
  return response.data.data.attributes;
};
