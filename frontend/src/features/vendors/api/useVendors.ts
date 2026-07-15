import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { vendorApi } from './vendorApi';
import type {
  VendorContactFormValues,
  VendorContractFormValues,
  VendorFormValues,
  VendorQuotationFormValues,
  VendorRatingFormValues,
} from '../types';

export const useVendorsDashboard = () =>
  useQuery({ queryKey: ['vendors', 'dashboard'], queryFn: vendorApi.dashboard, staleTime: 30_000 });

export const useVendors = (filters: Record<string, unknown> = {}) =>
  useQuery({ queryKey: ['vendors', 'list', filters], queryFn: () => vendorApi.list(filters) });

export const useVendor = (id: number) =>
  useQuery({ queryKey: ['vendors', 'details', id], queryFn: () => vendorApi.get(id), enabled: id > 0 });

export const useVendorPurchasingHistory = (id: number) =>
  useQuery({ queryKey: ['vendors', 'purchasing-history', id], queryFn: () => vendorApi.purchasingHistory(id), enabled: id > 0 });

export const useCreateVendor = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (data: VendorFormValues) => vendorApi.create(data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useUpdateVendor = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: VendorFormValues }) => vendorApi.update(id, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useArchiveVendor = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => vendorApi.delete(id),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useActivateVendor = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => vendorApi.activate(id),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

// Contacts
export const useVendorContacts = (vendorId?: number) =>
  useQuery({ queryKey: ['vendors', 'contacts', vendorId ?? 'all'], queryFn: () => vendorApi.listContacts(vendorId) });

export const useCreateVendorContact = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ vendorId, data }: { vendorId: number; data: VendorContactFormValues }) => vendorApi.createContact(vendorId, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useUpdateVendorContact = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ contactId, data }: { contactId: number; data: VendorContactFormValues }) => vendorApi.updateContact(contactId, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useDeleteVendorContact = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (contactId: number) => vendorApi.deleteContact(contactId),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

// Contracts
export const useVendorContracts = (vendorId?: number) =>
  useQuery({ queryKey: ['vendors', 'contracts', vendorId ?? 'all'], queryFn: () => vendorApi.listContracts(vendorId) });

export const useCreateVendorContract = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ vendorId, data }: { vendorId: number; data: VendorContractFormValues }) => vendorApi.createContract(vendorId, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useUpdateVendorContract = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ contractId, data }: { contractId: number; data: VendorContractFormValues }) => vendorApi.updateContract(contractId, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useDeleteVendorContract = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (contractId: number) => vendorApi.deleteContract(contractId),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

// Quotations
export const useVendorQuotations = (vendorId?: number) =>
  useQuery({ queryKey: ['vendors', 'quotations', vendorId ?? 'all'], queryFn: () => vendorApi.listQuotations(vendorId) });

export const useCreateVendorQuotation = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ vendorId, data }: { vendorId: number; data: VendorQuotationFormValues }) => vendorApi.createQuotation(vendorId, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useUpdateQuotationStatus = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ quotationId, status }: { quotationId: number; status: string }) => vendorApi.updateQuotationStatus(quotationId, status),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

export const useDeleteVendorQuotation = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (quotationId: number) => vendorApi.deleteQuotation(quotationId),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};

// Ratings
export const useVendorRatings = (vendorId: number) =>
  useQuery({ queryKey: ['vendors', 'ratings', vendorId], queryFn: () => vendorApi.listRatings(vendorId), enabled: vendorId > 0 });

export const useCreateVendorRating = () => {
  const client = useQueryClient();
  return useMutation({
    mutationFn: ({ vendorId, data }: { vendorId: number; data: VendorRatingFormValues }) => vendorApi.createRating(vendorId, data),
    onSuccess: () => void client.invalidateQueries({ queryKey: ['vendors'] }),
  });
};
