import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { purchasingApi } from './purchasingApi';
import type {
  GoodsReceiptFormValues,
  PurchaseOrderFormValues,
  PurchaseRequestFormValues,
  SupplierInvoiceFormValues,
} from '../types';

export const usePurchasingDashboard = () => useQuery({ queryKey: ['purchasing', 'dashboard'], queryFn: purchasingApi.dashboard, staleTime: 30_000 });

export const usePurchaseRequests = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['purchasing', 'requests', filters], queryFn: () => purchasingApi.requests(filters) });
export const usePurchaseRequest = (id: number) => useQuery({ queryKey: ['purchasing', 'requests', id], queryFn: () => purchasingApi.request(id), enabled: id > 0 });

export const usePurchaseOrders = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['purchasing', 'orders', filters], queryFn: () => purchasingApi.orders(filters) });
export const usePurchaseOrder = (id: number) => useQuery({ queryKey: ['purchasing', 'orders', id], queryFn: () => purchasingApi.order(id), enabled: id > 0 });

export const useGoodsReceipts = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['purchasing', 'receipts', filters], queryFn: () => purchasingApi.receipts(filters) });
export const useGoodsReceipt = (id: number) => useQuery({ queryKey: ['purchasing', 'receipts', id], queryFn: () => purchasingApi.receipt(id), enabled: id > 0 });

export const useSupplierInvoices = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['purchasing', 'invoices', filters], queryFn: () => purchasingApi.invoices(filters) });

export const useCreatePurchaseRequest = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: PurchaseRequestFormValues) => purchasingApi.createRequest(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useUpdatePurchaseRequest = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: ({ id, data }: { id: number; data: PurchaseRequestFormValues }) => purchasingApi.updateRequest(id, data), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useSubmitRequest = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (id: number) => purchasingApi.submitRequest(id), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useApproveRequest = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: ({ id, comments }: { id: number; comments?: string }) => purchasingApi.approveRequest(id, comments), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useRejectRequest = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: ({ id, comments }: { id: number; comments?: string }) => purchasingApi.rejectRequest(id, comments), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useCancelRequest = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (id: number) => purchasingApi.cancelRequest(id), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};

export const useCreatePurchaseOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: PurchaseOrderFormValues) => purchasingApi.createOrder(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useUpdatePurchaseOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: ({ id, data }: { id: number; data: PurchaseOrderFormValues }) => purchasingApi.updateOrder(id, data), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useSubmitOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (id: number) => purchasingApi.submitOrder(id), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useApproveOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: ({ id, comments }: { id: number; comments?: string }) => purchasingApi.approveOrder(id, comments), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useRejectOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: ({ id, comments }: { id: number; comments?: string }) => purchasingApi.rejectOrder(id, comments), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useCancelOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (id: number) => purchasingApi.cancelOrder(id), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
export const useCloseOrder = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (id: number) => purchasingApi.closeOrder(id), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};

export const useCreateGoodsReceipt = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: GoodsReceiptFormValues) => purchasingApi.createReceipt(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};

export const useCreateSupplierInvoice = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: SupplierInvoiceFormValues) => purchasingApi.createInvoice(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['purchasing'] }) });
};
