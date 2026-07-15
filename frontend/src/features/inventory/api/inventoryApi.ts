import { apiClient } from '@/lib/apiClient';
import type {
  Asset,
  AssetFormValues,
  AssetTimelineItem,
  CatalogItem,
  InventoryDashboard,
  PaginatedResponse,
  Product,
  ProductFormValues,
  StockBalance,
  StockMovement,
  TransferFormValues,
  Warehouse,
  WarehouseFormValues,
  WarehouseLocation,
  WarehouseTransfer,
} from '../types';
import type { StockOperationSchemaValues } from '../schemas';

const query = (values: Record<string, unknown>) => {
  const params = new URLSearchParams();
  Object.entries(values).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value));
  });
  return params.toString();
};
const attributes = <T>(payload: { data: { attributes: T } }) => payload.data.attributes;

export const inventoryApi = {
  dashboard: async () => (await apiClient.get<{ data: InventoryDashboard }>('/inventory/dashboard')).data.data,
  search: async (term: string) => (await apiClient.get<{ data: Record<string, Array<Record<string, unknown>>> }>(`/inventory/search?${query({ q: term })}`)).data.data,
  lookup: async (resource: string, search = '') => (await apiClient.get<{ data: Array<Record<string, unknown>> }>(`/inventory/lookups/${resource}?${query({ search })}`)).data.data,

  products: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<Product>>(`/inventory/products?${query(filters)}`)).data,
  product: async (id: number) => attributes((await apiClient.get<{ data: { attributes: Product } }>(`/inventory/products/${id}`)).data),
  createProduct: async (data: ProductFormValues) => attributes((await apiClient.post<{ data: { attributes: Product } }>('/inventory/products', data)).data),
  updateProduct: async (id: number, data: ProductFormValues) => attributes((await apiClient.put<{ data: { attributes: Product } }>(`/inventory/products/${id}`, data)).data),
  deleteProduct: async (id: number) => apiClient.delete(`/inventory/products/${id}`),

  catalog: async (resource: string) => (await apiClient.get<{ data: CatalogItem[] }>(`/inventory/${resource}`)).data.data,
  createCatalog: async (resource: string, data: Record<string, unknown>) => (await apiClient.post<{ data: CatalogItem }>(`/inventory/${resource}`, data)).data.data,
  updateCatalog: async (resource: string, id: number, data: Record<string, unknown>) => (await apiClient.put<{ data: CatalogItem }>(`/inventory/${resource}/${id}`, data)).data.data,
  deleteCatalog: async (resource: string, id: number) => apiClient.delete(`/inventory/${resource}/${id}`),

  warehouses: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<Warehouse>>(`/inventory/warehouses?${query(filters)}`)).data,
  warehouse: async (id: number) => attributes((await apiClient.get<{ data: { attributes: Warehouse } }>(`/inventory/warehouses/${id}`)).data),
  createWarehouse: async (data: WarehouseFormValues) => attributes((await apiClient.post<{ data: { attributes: Warehouse } }>('/inventory/warehouses', data)).data),
  updateWarehouse: async (id: number, data: WarehouseFormValues) => attributes((await apiClient.put<{ data: { attributes: Warehouse } }>(`/inventory/warehouses/${id}`, data)).data),
  deleteWarehouse: async (id: number) => apiClient.delete(`/inventory/warehouses/${id}`),
  locations: async (warehouseId: number) => (await apiClient.get<{ data: WarehouseLocation[] }>(`/inventory/warehouses/${warehouseId}/locations`)).data.data,
  createLocation: async (warehouseId: number, data: Record<string, unknown>) => (await apiClient.post<{ data: WarehouseLocation }>(`/inventory/warehouses/${warehouseId}/locations`, data)).data.data,

  assets: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<Asset>>(`/inventory/assets?${query(filters)}`)).data,
  asset: async (id: number) => attributes((await apiClient.get<{ data: { attributes: Asset } }>(`/inventory/assets/${id}`)).data),
  createAsset: async (data: AssetFormValues) => attributes((await apiClient.post<{ data: { attributes: Asset } }>('/inventory/assets', data)).data),
  updateAsset: async (id: number, data: AssetFormValues) => attributes((await apiClient.put<{ data: { attributes: Asset } }>(`/inventory/assets/${id}`, data)).data),
  deleteAsset: async (id: number) => apiClient.delete(`/inventory/assets/${id}`),
  assignAsset: async (id: number, data: Record<string, unknown>) => attributes((await apiClient.post<{ data: { attributes: Asset } }>(`/inventory/assets/${id}/assign`, data)).data),
  returnAsset: async (id: number, data: { warehouse_location_id: number; notes?: string }) => attributes((await apiClient.post<{ data: { attributes: Asset } }>(`/inventory/assets/${id}/return`, data)).data),
  changeAssetStatus: async (id: number, status: string, reason?: string) => attributes((await apiClient.patch<{ data: { attributes: Asset } }>(`/inventory/assets/${id}/status`, { status, reason })).data),
  assetTimeline: async (id: number) => (await apiClient.get<{ data: AssetTimelineItem[] }>(`/inventory/assets/${id}/timeline`)).data.data,

  stock: async (filters: Record<string, unknown> = {}) => (await apiClient.get<{ data: StockBalance[] }>(`/inventory/stock?${query(filters)}`)).data.data,
  movements: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<StockMovement>>(`/inventory/stock-movements?${query(filters)}`)).data,
  movement: async (id: number) => attributes((await apiClient.get<{ data: { attributes: StockMovement } }>(`/inventory/stock-movements/${id}`)).data),
  postMovement: async (operation: string, data: StockOperationSchemaValues) => attributes((await apiClient.post<{ data: { attributes: StockMovement } }>(`/inventory/stock-movements/${operation}`, data)).data),
  reverseMovement: async (id: number, reason: string) => attributes((await apiClient.post<{ data: { attributes: StockMovement } }>(`/inventory/stock-movements/${id}/reverse`, { reason })).data),

  transfers: async (filters: Record<string, unknown> = {}) => (await apiClient.get<PaginatedResponse<WarehouseTransfer>>(`/inventory/transfers?${query(filters)}`)).data,
  transfer: async (id: number) => attributes((await apiClient.get<{ data: { attributes: WarehouseTransfer } }>(`/inventory/transfers/${id}`)).data),
  createTransfer: async (data: TransferFormValues) => attributes((await apiClient.post<{ data: { attributes: WarehouseTransfer } }>('/inventory/transfers', data)).data),
  updateTransfer: async (id: number, data: TransferFormValues) => attributes((await apiClient.put<{ data: { attributes: WarehouseTransfer } }>(`/inventory/transfers/${id}`, data)).data),
  deleteTransfer: async (id: number) => apiClient.delete(`/inventory/transfers/${id}`),
  transferAction: async (id: number, action: string, data: Record<string, unknown> = {}) => attributes((await apiClient.post<{ data: { attributes: WarehouseTransfer } }>(`/inventory/transfers/${id}/${action}`, data)).data),
};
