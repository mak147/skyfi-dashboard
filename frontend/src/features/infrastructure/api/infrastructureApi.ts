import { apiClient } from '@/lib/apiClient';
import type {
  PopSite,
  Tower,
  Sector,
  NetworkDevice,
  PopSiteListFilters,
  TowerListFilters,
  SectorListFilters,
  NetworkDeviceListFilters,
  PaginatedResponse,
  InfrastructureDashboardPayload,
} from '../types';

const buildQueryString = (params: Record<string, unknown>): string => {
  const searchParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      searchParams.set(key, String(value));
    }
  });
  return searchParams.toString();
};

export const infrastructureApi = {
  // Dashboard
  getDashboard: async (): Promise<InfrastructureDashboardPayload> => {
    const response = await apiClient.get<{ data: InfrastructureDashboardPayload }>('/infrastructure/dashboard');
    return response.data.data;
  },

  // POP Sites
  getPopSites: async (filters: PopSiteListFilters = {}): Promise<PaginatedResponse<PopSite>> => {
    const query = buildQueryString(filters);
    const response = await apiClient.get<PaginatedResponse<PopSite>>(`/infrastructure/pop-sites?${query}`);
    return response.data;
  },

  getPopSite: async (id: number): Promise<PopSite> => {
    const response = await apiClient.get<{ data: { attributes: PopSite } }>(`/infrastructure/pop-sites/${id}`);
    return response.data.data.attributes;
  },

  createPopSite: async (data: Partial<PopSite>): Promise<PopSite> => {
    const response = await apiClient.post<{ data: { attributes: PopSite } }>('/infrastructure/pop-sites', data);
    return response.data.data.attributes;
  },

  updatePopSite: async (id: number, data: Partial<PopSite>): Promise<PopSite> => {
    const response = await apiClient.put<{ data: { attributes: PopSite } }>(`/infrastructure/pop-sites/${id}`, data);
    return response.data.data.attributes;
  },

  deletePopSite: async (id: number): Promise<void> => {
    await apiClient.delete(`/infrastructure/pop-sites/${id}`);
  },

  changePopSiteStatus: async (id: number, status: string): Promise<PopSite> => {
    const response = await apiClient.patch<{ data: { attributes: PopSite } }>(`/infrastructure/pop-sites/${id}/status`, { status });
    return response.data.data.attributes;
  },

  getPopSiteTowers: async (popSiteId: number): Promise<Tower[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: Tower }> }>(`/infrastructure/pop-sites/${popSiteId}/towers`);
    return response.data.data.map((d) => d.attributes);
  },

  getPopSiteMapPoints: async (): Promise<PopSite[]> => {
    const response = await apiClient.get<{ data: PopSite[] }>('/infrastructure/pop-sites/map-points');
    return response.data.data;
  },

  // Towers
  getTowers: async (filters: TowerListFilters = {}): Promise<PaginatedResponse<Tower>> => {
    const query = buildQueryString(filters);
    const response = await apiClient.get<PaginatedResponse<Tower>>(`/infrastructure/towers?${query}`);
    return response.data;
  },

  getTower: async (id: number): Promise<Tower> => {
    const response = await apiClient.get<{ data: { attributes: Tower } }>(`/infrastructure/towers/${id}`);
    return response.data.data.attributes;
  },

  createTower: async (data: Partial<Tower>): Promise<Tower> => {
    const response = await apiClient.post<{ data: { attributes: Tower } }>('/infrastructure/towers', data);
    return response.data.data.attributes;
  },

  updateTower: async (id: number, data: Partial<Tower>): Promise<Tower> => {
    const response = await apiClient.put<{ data: { attributes: Tower } }>(`/infrastructure/towers/${id}`, data);
    return response.data.data.attributes;
  },

  deleteTower: async (id: number): Promise<void> => {
    await apiClient.delete(`/infrastructure/towers/${id}`);
  },

  changeTowerStatus: async (id: number, status: string): Promise<Tower> => {
    const response = await apiClient.patch<{ data: { attributes: Tower } }>(`/infrastructure/towers/${id}/status`, { status });
    return response.data.data.attributes;
  },

  getTowerSectors: async (towerId: number): Promise<Sector[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: Sector }> }>(`/infrastructure/towers/${towerId}/sectors`);
    return response.data.data.map((d) => d.attributes);
  },

  getTowerDevices: async (towerId: number): Promise<NetworkDevice[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: NetworkDevice }> }>(`/infrastructure/towers/${towerId}/devices`);
    return response.data.data.map((d) => d.attributes);
  },

  getTowerMapPoints: async (): Promise<Tower[]> => {
    const response = await apiClient.get<{ data: Tower[] }>('/infrastructure/towers/map-points');
    return response.data.data;
  },

  getTowersByPopSite: async (popSiteId: number): Promise<Tower[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: Tower }> }>(`/infrastructure/pop-sites/${popSiteId}/towers`);
    return response.data.data.map((d) => d.attributes);
  },

  // Sectors
  getSectors: async (filters: SectorListFilters = {}): Promise<PaginatedResponse<Sector>> => {
    const query = buildQueryString(filters);
    const response = await apiClient.get<PaginatedResponse<Sector>>(`/infrastructure/sectors?${query}`);
    return response.data;
  },

  getSector: async (id: number): Promise<Sector> => {
    const response = await apiClient.get<{ data: { attributes: Sector } }>(`/infrastructure/sectors/${id}`);
    return response.data.data.attributes;
  },

  createSector: async (data: Partial<Sector>): Promise<Sector> => {
    const response = await apiClient.post<{ data: { attributes: Sector } }>('/infrastructure/sectors', data);
    return response.data.data.attributes;
  },

  updateSector: async (id: number, data: Partial<Sector>): Promise<Sector> => {
    const response = await apiClient.put<{ data: { attributes: Sector } }>(`/infrastructure/sectors/${id}`, data);
    return response.data.data.attributes;
  },

  deleteSector: async (id: number): Promise<void> => {
    await apiClient.delete(`/infrastructure/sectors/${id}`);
  },

  changeSectorStatus: async (id: number, status: string): Promise<Sector> => {
    const response = await apiClient.patch<{ data: { attributes: Sector } }>(`/infrastructure/sectors/${id}/status`, { status });
    return response.data.data.attributes;
  },

  getSectorConnections: async (sectorId: number): Promise<Sector> => {
    const response = await apiClient.get<{ data: { attributes: Sector } }>(`/infrastructure/sectors/${sectorId}/connections`);
    return response.data.data.attributes;
  },

  getCoverageData: async (): Promise<Sector[]> => {
    const response = await apiClient.get<{ data: Sector[] }>('/infrastructure/sectors/coverage');
    return response.data.data;
  },

  getSectorsByTower: async (towerId: number): Promise<Sector[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: Sector }> }>(`/infrastructure/towers/${towerId}/sectors`);
    return response.data.data.map((d) => d.attributes);
  },

  // Network Devices
  getDevices: async (filters: NetworkDeviceListFilters = {}): Promise<PaginatedResponse<NetworkDevice>> => {
    const query = buildQueryString(filters);
    const response = await apiClient.get<PaginatedResponse<NetworkDevice>>(`/infrastructure/devices?${query}`);
    return response.data;
  },

  getDevice: async (id: number): Promise<NetworkDevice> => {
    const response = await apiClient.get<{ data: { attributes: NetworkDevice } }>(`/infrastructure/devices/${id}`);
    return response.data.data.attributes;
  },

  createDevice: async (data: Partial<NetworkDevice>): Promise<NetworkDevice> => {
    const response = await apiClient.post<{ data: { attributes: NetworkDevice } }>('/infrastructure/devices', data);
    return response.data.data.attributes;
  },

  updateDevice: async (id: number, data: Partial<NetworkDevice>): Promise<NetworkDevice> => {
    const response = await apiClient.put<{ data: { attributes: NetworkDevice } }>(`/infrastructure/devices/${id}`, data);
    return response.data.data.attributes;
  },

  deleteDevice: async (id: number): Promise<void> => {
    await apiClient.delete(`/infrastructure/devices/${id}`);
  },

  changeDeviceStatus: async (id: number, status: string): Promise<NetworkDevice> => {
    const response = await apiClient.patch<{ data: { attributes: NetworkDevice } }>(`/infrastructure/devices/${id}/status`, { status });
    return response.data.data.attributes;
  },

  getDevicesByType: async (type: string): Promise<NetworkDevice[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: NetworkDevice }> }>(`/infrastructure/devices/by-type/${type}`);
    return response.data.data.map((d) => d.attributes);
  },

  getDevicesByPopSite: async (popSiteId: number): Promise<NetworkDevice[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: NetworkDevice }> }>(`/infrastructure/pop-sites/${popSiteId}/devices`);
    return response.data.data.map((d) => d.attributes);
  },

  getDevicesByTower: async (towerId: number): Promise<NetworkDevice[]> => {
    const response = await apiClient.get<{ data: Array<{ attributes: NetworkDevice }> }>(`/infrastructure/towers/${towerId}/devices`);
    return response.data.data.map((d) => d.attributes);
  },
};
