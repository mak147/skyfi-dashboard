import { apiClient } from '@/lib/apiClient';

import type { AuthSession, LoginPayload } from '../types';

interface AuthResourceResponse {
  data: {
    attributes: AuthSession;
  };
}

const unwrapSession = (response: AuthResourceResponse): AuthSession => response.data.attributes;

export const login = async (payload: LoginPayload): Promise<AuthSession> => {
  const response = await apiClient.post<AuthResourceResponse>('/auth/login', payload);
  return unwrapSession(response.data);
};

export const refresh = async (): Promise<AuthSession> => {
  const response = await apiClient.post<AuthResourceResponse>('/auth/refresh');
  return unwrapSession(response.data);
};

export const logout = async (): Promise<void> => {
  await apiClient.post('/auth/logout');
};
