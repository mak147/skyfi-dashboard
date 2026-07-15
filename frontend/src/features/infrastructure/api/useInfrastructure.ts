import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { infrastructureApi } from './infrastructureApi';
import type {
  PopSite,
  Tower,
  Sector,
  NetworkDevice,
  PopSiteListFilters,
  TowerListFilters,
  SectorListFilters,
  NetworkDeviceListFilters,
} from '../types';

export const usePopSites = (filters: PopSiteListFilters = {}) => useQuery({
    queryKey: ['popSites', filters],
    queryFn: () => infrastructureApi.getPopSites(filters),
    staleTime: 30_000,
  });

export const usePopSite = (id: number | null) => useQuery({
    queryKey: ['popSite', id],
    queryFn: () => infrastructureApi.getPopSite(id!),
    enabled: id !== null,
    staleTime: 30_000,
  });

export const usePopSiteTowers = (popSiteId: number | null) => useQuery({
    queryKey: ['popSiteTowers', popSiteId],
    queryFn: () => infrastructureApi.getPopSiteTowers(popSiteId!),
    enabled: popSiteId !== null,
    staleTime: 30_000,
  });

export const usePopSiteMapPoints = () => useQuery({
    queryKey: ['popSiteMapPoints'],
    queryFn: infrastructureApi.getPopSiteMapPoints,
    staleTime: 60_000,
  });

export const useCreatePopSite = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.createPopSite,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['popSites'] });
    },
  });
};

export const useUpdatePopSite = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<PopSite> }) => infrastructureApi.updatePopSite(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['popSites'] });
    },
  });
};

export const useDeletePopSite = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.deletePopSite,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['popSites'] });
    },
  });
};

export const useChangePopSiteStatus = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => infrastructureApi.changePopSiteStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['popSites'] });
    },
  });
};

// Towers
export const useTowers = (filters: TowerListFilters = {}) => useQuery({
    queryKey: ['towers', filters],
    queryFn: () => infrastructureApi.getTowers(filters),
    staleTime: 30_000,
  });

export const useTower = (id: number | null) => useQuery({
    queryKey: ['tower', id],
    queryFn: () => infrastructureApi.getTower(id!),
    enabled: id !== null,
    staleTime: 30_000,
  });

export const useTowerSectors = (towerId: number | null) => useQuery({
    queryKey: ['towerSectors', towerId],
    queryFn: () => infrastructureApi.getTowerSectors(towerId!),
    enabled: towerId !== null,
    staleTime: 30_000,
  });

export const useTowerDevices = (towerId: number | null) => useQuery({
    queryKey: ['towerDevices', towerId],
    queryFn: () => infrastructureApi.getTowerDevices(towerId!),
    enabled: towerId !== null,
    staleTime: 30_000,
  });

export const useTowerMapPoints = () => useQuery({
    queryKey: ['towerMapPoints'],
    queryFn: infrastructureApi.getTowerMapPoints,
    staleTime: 60_000,
  });

export const useTowersByPopSite = (popSiteId: number | null) => useQuery({
    queryKey: ['towersByPopSite', popSiteId],
    queryFn: () => infrastructureApi.getTowersByPopSite(popSiteId!),
    enabled: popSiteId !== null,
    staleTime: 30_000,
  });

export const useCreateTower = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.createTower,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['towers'] });
    },
  });
};

export const useUpdateTower = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Tower> }) => infrastructureApi.updateTower(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['towers'] });
    },
  });
};

export const useDeleteTower = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.deleteTower,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['towers'] });
    },
  });
};

export const useChangeTowerStatus = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => infrastructureApi.changeTowerStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['towers'] });
    },
  });
};

// Sectors
export const useSectors = (filters: SectorListFilters = {}) => useQuery({
    queryKey: ['sectors', filters],
    queryFn: () => infrastructureApi.getSectors(filters),
    staleTime: 30_000,
  });

export const useSector = (id: number | null) => useQuery({
    queryKey: ['sector', id],
    queryFn: () => infrastructureApi.getSector(id!),
    enabled: id !== null,
    staleTime: 30_000,
  });

export const useSectorConnections = (sectorId: number | null) => useQuery({
    queryKey: ['sectorConnections', sectorId],
    queryFn: () => infrastructureApi.getSectorConnections(sectorId!),
    enabled: sectorId !== null,
    staleTime: 30_000,
  });

export const useCoverageData = () => useQuery({
    queryKey: ['coverageData'],
    queryFn: infrastructureApi.getCoverageData,
    staleTime: 60_000,
  });

export const useSectorsByTower = (towerId: number | null) => useQuery({
    queryKey: ['sectorsByTower', towerId],
    queryFn: () => infrastructureApi.getSectorsByTower(towerId!),
    enabled: towerId !== null,
    staleTime: 30_000,
  });

export const useCreateSector = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.createSector,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sectors'] });
    },
  });
};

export const useUpdateSector = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Sector> }) => infrastructureApi.updateSector(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sectors'] });
    },
  });
};

export const useDeleteSector = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.deleteSector,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sectors'] });
    },
  });
};

export const useChangeSectorStatus = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => infrastructureApi.changeSectorStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sectors'] });
    },
  });
};

// Network Devices
export const useDevices = (filters: NetworkDeviceListFilters = {}) => useQuery({
    queryKey: ['devices', filters],
    queryFn: () => infrastructureApi.getDevices(filters),
    staleTime: 30_000,
  });

export const useDevice = (id: number | null) => useQuery({
    queryKey: ['device', id],
    queryFn: () => infrastructureApi.getDevice(id!),
    enabled: id !== null,
    staleTime: 30_000,
  });

export const useDevicesByType = (type: string | null) => useQuery({
    queryKey: ['devicesByType', type],
    queryFn: () => infrastructureApi.getDevicesByType(type!),
    enabled: type !== null,
    staleTime: 30_000,
  });

export const useDevicesByPopSite = (popSiteId: number | null) => useQuery({
    queryKey: ['devicesByPopSite', popSiteId],
    queryFn: () => infrastructureApi.getDevicesByPopSite(popSiteId!),
    enabled: popSiteId !== null,
    staleTime: 30_000,
  });

export const useDevicesByTower = (towerId: number | null) => useQuery({
    queryKey: ['devicesByTower', towerId],
    queryFn: () => infrastructureApi.getDevicesByTower(towerId!),
    enabled: towerId !== null,
    staleTime: 30_000,
  });

export const useCreateDevice = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.createDevice,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['devices'] });
    },
  });
};

export const useUpdateDevice = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<NetworkDevice> }) => infrastructureApi.updateDevice(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['devices'] });
    },
  });
};

export const useDeleteDevice = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: infrastructureApi.deleteDevice,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['devices'] });
    },
  });
};

export const useChangeDeviceStatus = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => infrastructureApi.changeDeviceStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['devices'] });
    },
  });
};

// Dashboard
export const useInfrastructureDashboard = () => useQuery({
    queryKey: ['infrastructureDashboard'],
    queryFn: infrastructureApi.getDashboard,
    staleTime: 30_000,
  });
