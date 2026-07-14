import { apiClient } from '@/lib/apiClient';

import type {
  BulkGenerateData,
  GenerateInvoiceData,
  Invoice,
  InvoiceFilters,
  InvoiceFormData,
  InvoiceListResponse,
  BillingStatistics,
  InvoiceActivity,
} from '../types';

interface SingleResponse {
  data: {
    type: 'invoices';
    id: string;
    attributes: Invoice;
  };
}

export const getInvoices = async (
  page: number,
  perPage: number,
  filters: InvoiceFilters,
  sort: string,
): Promise<InvoiceListResponse> => {
  const params = new URLSearchParams();
  params.set('page[number]', String(page));
  params.set('page[size]', String(perPage));
  params.set('sort', sort);

  if (filters.status) params.set('filter[status]', filters.status);
  if (filters.customer_id) params.set('filter[customer_id]', filters.customer_id);
  if (filters.due_from) params.set('filter[due_from]', filters.due_from);
  if (filters.due_to) params.set('filter[due_to]', filters.due_to);
  if (filters.search) params.set('filter[search]', filters.search);

  const response = await apiClient.get<InvoiceListResponse>(`/invoices?${params.toString()}`);
  return response.data;
};

export const getInvoice = async (id: number): Promise<Invoice> => {
  const response = await apiClient.get<SingleResponse>(`/invoices/${id}`);
  return response.data.data.attributes;
};

export const createInvoice = async (data: InvoiceFormData): Promise<Invoice> => {
  const payload = {
    ...data,
    customer_id: Number(data.customer_id),
    connection_id: Number(data.connection_id),
    package_id: Number(data.package_id),
    previous_balance: Number(data.previous_balance) || 0,
    items: data.items.map((item) => ({
      ...item,
      quantity: Number(item.quantity) || 1,
      unit_price: Number(item.unit_price) || 0,
      tax_amount: Number(item.tax_amount) || 0,
      discount_amount: Number(item.discount_amount) || 0,
    })),
  };
  const response = await apiClient.post<SingleResponse>('/invoices', payload);
  return response.data.data.attributes;
};

export const updateInvoice = async (id: number, data: Partial<InvoiceFormData>): Promise<Invoice> => {
  const payload: Record<string, unknown> = {};
  if (data.notes !== undefined) payload.notes = data.notes;
  if (data.due_date !== undefined) payload.due_date = data.due_date;
  if (data.items !== undefined) {
    payload.items = data.items.map((item) => ({
      ...item,
      quantity: Number(item.quantity) || 1,
      unit_price: Number(item.unit_price) || 0,
      tax_amount: Number(item.tax_amount) || 0,
      discount_amount: Number(item.discount_amount) || 0,
    }));
  }
  const response = await apiClient.put<SingleResponse>(`/invoices/${id}`, payload);
  return response.data.data.attributes;
};

export const deleteInvoice = async (id: number): Promise<void> => {
  await apiClient.delete(`/invoices/${id}`);
};

export const changeInvoiceStatus = async (id: number, status: string): Promise<Invoice> => {
  const response = await apiClient.patch<SingleResponse>(`/invoices/${id}/status`, { status });
  return response.data.data.attributes;
};

export const generateInvoice = async (data: GenerateInvoiceData): Promise<Invoice> => {
  const payload = {
    ...data,
    connection_id: Number(data.connection_id),
  };
  const response = await apiClient.post<SingleResponse>('/invoices/generate', payload);
  return response.data.data.attributes;
};

export const bulkGenerateInvoices = async (data: BulkGenerateData): Promise<{ generated: number; failed: number; errors: Array<{ connection_id: number; error: string }> }> => {
  const response = await apiClient.post<{ data: { generated: number; failed: number; errors: Array<{ connection_id: number; error: string }> } }>('/invoices/bulk-generate', data);
  return response.data.data;
};

export const getBillingStatistics = async (): Promise<BillingStatistics> => {
  const response = await apiClient.get<{ data: BillingStatistics }>('/invoices/statistics');
  return response.data.data;
};

export const getInvoiceActivities = async (id: number): Promise<InvoiceActivity[]> => {
  const response = await apiClient.get<{ data: InvoiceActivity[] }>(`/invoices/${id}/activity`);
  return response.data.data;
};
