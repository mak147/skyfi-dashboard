import { apiClient } from '@/lib/apiClient';
import type { ApiCollection, ApiResource, Branch, BrandingSettings, CompanySettings, Department, LocalizationSettings, NotificationSettings, SystemDashboard, SystemSettings } from '../types';
const attrs = <T>(response: ApiResource<T>) => response.data.attributes;
const list = <T extends { id: number }>(response: ApiCollection<T>) => response.data.map((item) => ({ ...item.attributes, id: Number(item.id) }));
export const systemApi = {
  dashboard: async () => (await apiClient.get<{ data: SystemDashboard }>('/system/dashboard')).data.data,
  configuration: async () => (await apiClient.get<{ data: unknown }>('/system/configuration')).data.data,
  company: async () => attrs((await apiClient.get<ApiResource<CompanySettings>>('/system/company')).data),
  updateCompany: async (data: Partial<CompanySettings>) => attrs((await apiClient.put<ApiResource<CompanySettings>>('/system/company', data)).data),
  branches: async (filters: Record<string, unknown> = {}) => list((await apiClient.get<ApiCollection<Branch>>('/system/branches', { params: filters })).data),
  createBranch: async (data: Partial<Branch>) => attrs((await apiClient.post<ApiResource<Branch>>('/system/branches', data)).data),
  updateBranch: async (id: number, data: Partial<Branch>) => attrs((await apiClient.put<ApiResource<Branch>>(`/system/branches/${id}`, data)).data),
  deleteBranch: async (id: number) => { await apiClient.delete(`/system/branches/${id}`); },
  setBranchStatus: async (id: number, status: 'active' | 'inactive') => attrs((await apiClient.post<ApiResource<Branch>>(`/system/branches/${id}/${status === 'active' ? 'activate' : 'deactivate'}`)).data),
  departments: async (filters: Record<string, unknown> = {}) => list((await apiClient.get<ApiCollection<Department>>('/system/departments', { params: filters })).data),
  createDepartment: async (data: Partial<Department>) => attrs((await apiClient.post<ApiResource<Department>>('/system/departments', data)).data),
  updateDepartment: async (id: number, data: Partial<Department>) => attrs((await apiClient.put<ApiResource<Department>>(`/system/departments/${id}`, data)).data),
  deleteDepartment: async (id: number) => { await apiClient.delete(`/system/departments/${id}`); },
  setDepartmentStatus: async (id: number, status: 'active' | 'inactive') => attrs((await apiClient.post<ApiResource<Department>>(`/system/departments/${id}/${status === 'active' ? 'activate' : 'deactivate'}`)).data),
  settings: async () => attrs((await apiClient.get<ApiResource<SystemSettings>>('/system/settings')).data),
  updateSettings: async (data: Partial<SystemSettings>) => attrs((await apiClient.put<ApiResource<SystemSettings>>('/system/settings', data)).data),
  branding: async () => attrs((await apiClient.get<ApiResource<BrandingSettings>>('/system/branding')).data),
  updateBranding: async (data: Partial<BrandingSettings>) => attrs((await apiClient.put<ApiResource<BrandingSettings>>('/system/branding', data)).data),
  uploadBrandingAsset: async (file: File, type: string) => { const form = new FormData(); form.append('file', file); form.append('type', type); return (await apiClient.post('/system/branding/assets', form, { headers: { 'Content-Type': 'multipart/form-data' }, params: { type } })).data; },
  localization: async () => attrs((await apiClient.get<ApiResource<LocalizationSettings>>('/system/localization')).data),
  updateLocalization: async (data: Partial<LocalizationSettings>) => attrs((await apiClient.put<ApiResource<LocalizationSettings>>('/system/localization', data)).data),
  localizationOptions: async () => (await apiClient.get<{ data: { languages: string[]; timezones: string[]; currencies: string[]; date_formats: string[] } }>('/system/localization/options')).data.data,
  notifications: async () => attrs((await apiClient.get<ApiResource<NotificationSettings>>('/system/notifications')).data),
  updateNotifications: async (data: Partial<NotificationSettings>) => attrs((await apiClient.put<ApiResource<NotificationSettings>>('/system/notifications', data)).data),
};
