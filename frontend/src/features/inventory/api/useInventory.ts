import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { inventoryApi } from './inventoryApi';
import type { AssetFormValues, ProductFormValues, TransferFormValues, WarehouseFormValues } from '../types';

export const useInventoryDashboard = () => useQuery({ queryKey: ['inventory', 'dashboard'], queryFn: inventoryApi.dashboard, staleTime: 30_000 });
export const useInventoryProducts = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['inventory', 'products', filters], queryFn: () => inventoryApi.products(filters) });
export const useInventoryAssets = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['inventory', 'assets', filters], queryFn: () => inventoryApi.assets(filters) });
export const useWarehouses = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['inventory', 'warehouses', filters], queryFn: () => inventoryApi.warehouses(filters) });
export const useStockMovements = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['inventory', 'movements', filters], queryFn: () => inventoryApi.movements(filters) });
export const useTransfers = (filters: Record<string, unknown> = {}) => useQuery({ queryKey: ['inventory', 'transfers', filters], queryFn: () => inventoryApi.transfers(filters) });

export const useCreateProduct = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: ProductFormValues) => inventoryApi.createProduct(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['inventory'] }) });
};
export const useCreateAsset = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: AssetFormValues) => inventoryApi.createAsset(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['inventory'] }) });
};
export const useCreateWarehouse = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: WarehouseFormValues) => inventoryApi.createWarehouse(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['inventory'] }) });
};
export const useCreateTransfer = () => {
  const client = useQueryClient();
  return useMutation({ mutationFn: (data: TransferFormValues) => inventoryApi.createTransfer(data), onSuccess: () => void client.invalidateQueries({ queryKey: ['inventory'] }) });
};
