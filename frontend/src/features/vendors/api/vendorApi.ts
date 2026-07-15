import { apiClient } from '@/lib/apiClient';
import type {
  PaginatedResponse,
  Resource,
  Vendor,
  VendorContact,
  VendorContactFormValues,
  VendorContract,
  VendorContractFormValues,
  VendorDashboardWidgets,
  VendorFormValues,
  VendorPurchasingHistory,
  VendorQuotation,
  VendorQuotationFormValues,
  VendorRating,
  VendorRatingFormValues,
} from '../types';

const query = (values: Record<string, unknown>) => {
  const params = new URLSearchParams();
  Object.entries(values).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value));
  });
  return params.toString();
};

const attributes = <T>(payload: { data: { attributes: T } }) => payload.data.attributes;

export const vendorApi = {
  // Dashboard
  dashboard: async () => (await apiClient.get<{ data: VendorDashboardWidgets }>('/vendors/dashboard')).data.data,

  // Vendors CRUD
  list: async (filters: Record<string, unknown> = {}) =>
    (await apiClient.get<PaginatedResponse<Vendor>>(`/vendors?${query(filters)}`)).data,
  get: async (id: number) =>
    attributes((await apiClient.get<{ data: { attributes: Vendor } }>(`/vendors/${id}`)).data),
  create: async (data: VendorFormValues) =>
    attributes((await apiClient.post<{ data: { attributes: Vendor } }>('/vendors', data)).data),
  update: async (id: number, data: VendorFormValues) =>
    attributes((await apiClient.put<{ data: { attributes: Vendor } }>(`/vendors/${id}`, data)).data),
  delete: async (id: number) =>
    attributes((await apiClient.delete<{ data: { attributes: Vendor } }>(`/vendors/${id}`)).data),
  activate: async (id: number) =>
    attributes((await apiClient.post<{ data: { attributes: Vendor } }>(`/vendors/${id}/activate`, {})).data),
  purchasingHistory: async (id: number) =>
    (await apiClient.get<{ data: VendorPurchasingHistory }>(`/vendors/${id}/purchasing-history`)).data.data,

  // Contacts
  listContacts: async (vendorId?: number) => {
    const url = vendorId ? `/vendors/${vendorId}/contacts` : '/vendors/contacts';
    return (await apiClient.get<{ data: Resource<VendorContact>[] }>(url)).data.data.map((r) => r.attributes);
  },
  createContact: async (vendorId: number, data: VendorContactFormValues) =>
    attributes((await apiClient.post<{ data: { attributes: VendorContact } }>(`/vendors/${vendorId}/contacts`, data)).data),
  updateContact: async (contactId: number, data: VendorContactFormValues) =>
    attributes((await apiClient.put<{ data: { attributes: VendorContact } }>(`/vendors/contacts/${contactId}`, data)).data),
  deleteContact: async (contactId: number) =>
    apiClient.delete(`/vendors/contacts/${contactId}`),

  // Contracts
  listContracts: async (vendorId?: number) => {
    const url = vendorId ? `/vendors/${vendorId}/contracts` : '/vendors/contracts';
    return (await apiClient.get<{ data: Resource<VendorContract>[] }>(url)).data.data.map((r) => r.attributes);
  },
  createContract: async (vendorId: number, data: VendorContractFormValues) =>
    attributes((await apiClient.post<{ data: { attributes: VendorContract } }>(`/vendors/${vendorId}/contracts`, data)).data),
  updateContract: async (contractId: number, data: VendorContractFormValues) =>
    attributes((await apiClient.put<{ data: { attributes: VendorContract } }>(`/vendors/contracts/${contractId}`, data)).data),
  deleteContract: async (contractId: number) =>
    apiClient.delete(`/vendors/contracts/${contractId}`),

  // Quotations
  listQuotations: async (vendorId?: number) => {
    const url = vendorId ? `/vendors/${vendorId}/quotations` : '/vendors/quotations';
    return (await apiClient.get<{ data: Resource<VendorQuotation>[] }>(url)).data.data.map((r) => r.attributes);
  },
  createQuotation: async (vendorId: number, data: VendorQuotationFormValues) =>
    attributes((await apiClient.post<{ data: { attributes: VendorQuotation } }>(`/vendors/${vendorId}/quotations`, data)).data),
  updateQuotationStatus: async (quotationId: number, status: string) =>
    attributes((await apiClient.put<{ data: { attributes: VendorQuotation } }>(`/vendors/quotations/${quotationId}`, { status })).data),
  deleteQuotation: async (quotationId: number) =>
    apiClient.delete(`/vendors/quotations/${quotationId}`),

  // Ratings
  listRatings: async (vendorId: number) =>
    (await apiClient.get<{ data: Resource<VendorRating>[] }>(`/vendors/${vendorId}/ratings`)).data.data.map((r) => r.attributes),
  createRating: async (vendorId: number, data: VendorRatingFormValues) =>
    attributes((await apiClient.post<{ data: { attributes: VendorRating } }>(`/vendors/${vendorId}/ratings`, data)).data),
};
