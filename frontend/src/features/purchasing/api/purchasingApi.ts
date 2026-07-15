import { apiClient } from '@/lib/apiClient';
import type {
  GoodsReceipt,
  GoodsReceiptFormValues,
  PaginatedResponse,
  ProcurementDashboard,
  PurchaseOrder,
  PurchaseOrderFormValues,
  PurchaseRequest,
  PurchaseRequestFormValues,
  SupplierInvoice,
  SupplierInvoiceFormValues,
} from '../types';

const query = (values: Record<string, unknown>) => {
  const params = new URLSearchParams();
  Object.entries(values).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value));
  });
  return params.toString();
};
const attributes = <T>(payload: { data: { attributes: T } }) => payload.data.attributes;

export const purchasingApi = {
  // Dashboard
  dashboard: async () => (await apiClient.get<{ data: ProcurementDashboard }>('/purchasing/dashboard')).data.data,

  // Purchase Requests
  requests: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<PurchaseRequest>>(`/purchasing/requests?${query(filters)}`)).data,
  request: async (id: number) => attributes((await apiClient.get<{ data: { attributes: PurchaseRequest } }>(`/purchasing/requests/${id}`)).data),
  createRequest: async (data: PurchaseRequestFormValues) => attributes((await apiClient.post<{ data: { attributes: PurchaseRequest } }>('/purchasing/requests', data)).data),
  updateRequest: async (id: number, data: PurchaseRequestFormValues) => attributes((await apiClient.put<{ data: { attributes: PurchaseRequest } }>(`/purchasing/requests/${id}`, data)).data),
  submitRequest: async (id: number) => attributes((await apiClient.post<{ data: { attributes: PurchaseRequest } }>(`/purchasing/requests/${id}/submit`, {})).data),
  approveRequest: async (id: number, comments?: string) => attributes((await apiClient.post<{ data: { attributes: PurchaseRequest } }>(`/purchasing/requests/${id}/approve`, { comments })).data),
  rejectRequest: async (id: number, comments?: string) => attributes((await apiClient.post<{ data: { attributes: PurchaseRequest } }>(`/purchasing/requests/${id}/reject`, { comments })).data),
  cancelRequest: async (id: number) => attributes((await apiClient.post<{ data: { attributes: PurchaseRequest } }>(`/purchasing/requests/${id}/cancel`, {})).data),

  // Purchase Orders
  orders: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<PurchaseOrder>>(`/purchasing/orders?${query(filters)}`)).data,
  order: async (id: number) => attributes((await apiClient.get<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}`)).data),
  createOrder: async (data: PurchaseOrderFormValues) => attributes((await apiClient.post<{ data: { attributes: PurchaseOrder } }>('/purchasing/orders', data)).data),
  updateOrder: async (id: number, data: PurchaseOrderFormValues) => attributes((await apiClient.put<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}`, data)).data),
  submitOrder: async (id: number) => attributes((await apiClient.post<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}/submit`, {})).data),
  approveOrder: async (id: number, comments?: string) => attributes((await apiClient.post<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}/approve`, { comments })).data),
  rejectOrder: async (id: number, comments?: string) => attributes((await apiClient.post<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}/reject`, { comments })).data),
  cancelOrder: async (id: number) => attributes((await apiClient.post<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}/cancel`, {})).data),
  closeOrder: async (id: number) => attributes((await apiClient.post<{ data: { attributes: PurchaseOrder } }>(`/purchasing/orders/${id}/close`, {})).data),

  // Goods Receipts
  receipts: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<GoodsReceipt>>(`/purchasing/goods-receipts?${query(filters)}`)).data,
  receipt: async (id: number) => attributes((await apiClient.get<{ data: { attributes: GoodsReceipt } }>(`/purchasing/goods-receipts/${id}`)).data),
  createReceipt: async (data: GoodsReceiptFormValues) => attributes((await apiClient.post<{ data: { attributes: GoodsReceipt } }>('/purchasing/goods-receipts', data)).data),
  returnReceipt: async (id: number) => attributes((await apiClient.post<{ data: { attributes: GoodsReceipt } }>(`/purchasing/goods-receipts/${id}/return`, {})).data),

  // Supplier Invoices
  invoices: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<SupplierInvoice>>(`/purchasing/supplier-invoices?${query(filters)}`)).data,
  invoice: async (id: number) => attributes((await apiClient.get<{ data: { attributes: SupplierInvoice } }>(`/purchasing/supplier-invoices/${id}`)).data),
  createInvoice: async (data: SupplierInvoiceFormValues) => attributes((await apiClient.post<{ data: { attributes: SupplierInvoice } }>('/purchasing/supplier-invoices', data)).data),
  updateInvoice: async (id: number, data: SupplierInvoiceFormValues) => attributes((await apiClient.put<{ data: { attributes: SupplierInvoice } }>(`/purchasing/supplier-invoices/${id}`, data)).data),
};
