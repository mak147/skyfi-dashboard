import { apiClient } from '@/lib/apiClient';
import type { Role, Permission, RoleFormData } from '../types';

interface ListResponse<T> {
  data: T[];
}

interface SingleResponse<T> {
  data: T;
}

export const getRoles = async (): Promise<Role[]> => {
  const response = await apiClient.get<ListResponse<Role>>('/roles');
  return response.data.data;
};

export const getRole = async (id: number): Promise<Role> => {
  const response = await apiClient.get<SingleResponse<Role>>(`/roles/${id}`);
  return response.data.data;
};

export const createRole = async (data: RoleFormData): Promise<Role> => {
  const response = await apiClient.post<SingleResponse<Role>>('/roles', data);
  return response.data.data;
};

export const updateRole = async (id: number, data: RoleFormData): Promise<Role> => {
  const response = await apiClient.put<SingleResponse<Role>>(`/roles/${id}`, data);
  return response.data.data;
};

export const deleteRole = async (id: number): Promise<void> => {
  await apiClient.delete(`/roles/${id}`);
};

export const getPermissions = async (): Promise<Permission[]> => {
  const response = await apiClient.get<ListResponse<Permission>>('/permissions');
  return response.data.data;
};

export const getUserRoles = async (userId: number): Promise<Role[]> => {
  const response = await apiClient.get<ListResponse<Role>>(`/users/${userId}/roles`);
  return response.data.data;
};

export const syncUserRoles = async (userId: number, roleIds: number[]): Promise<Role[]> => {
  const response = await apiClient.put<ListResponse<Role>>(`/users/${userId}/roles`, { roles: roleIds });
  return response.data.data;
};
