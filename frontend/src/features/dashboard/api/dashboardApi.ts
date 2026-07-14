import { apiClient } from '@/lib/apiClient';

import type { DashboardPayload } from '../types';

interface DashboardResourceResponse {
  data: {
    type: 'dashboard';
    id: string;
    attributes: DashboardPayload;
  };
}

export const getDashboard = async (): Promise<DashboardPayload> => {
  const response = await apiClient.get<DashboardResourceResponse>('/dashboard');
  return response.data.data.attributes;
};
