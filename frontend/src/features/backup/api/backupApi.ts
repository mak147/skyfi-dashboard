import { api } from '@/lib/api';
import type { 
  BackupJob, 
  BackupFile, 
  BackupSchedule, 
  StorageProvider, 
  RestoreHistory, 
  DrPlan, 
  BackupStatistics 
} from '../types';

export const backupApi = {
  getStatistics: () => api.get<BackupStatistics>('/backup/statistics'),
  
  getJobs: (params: any) => api.get<{ items: BackupJob[]; total: number }>('/backup/jobs', { params }),
  
  runManualBackup: (type: string) => api.post<BackupJob>('/backup/jobs/manual', { type }),
  
  getFiles: (params: any) => api.get<{ items: BackupFile[]; total: number }>('/backup/files', { params }),
  
  verifyFile: (id: number) => api.post<{ status: string; details: string }>(`/backup/files/${id}/verify`),
  
  getVerificationHistory: (id: number) => api.get<any[]>(`/backup/files/${id}/verification-history`),
  
  getSchedules: () => api.get<BackupSchedule[]>('/backup/schedules'),
  
  createSchedule: (data: any) => api.post<BackupSchedule>('/backup/schedules', data),
  
  updateSchedule: (id: number, data: any) => api.put<BackupSchedule>(`/backup/schedules/${id}`, data),
  
  deleteSchedule: (id: number) => api.delete(`/backup/schedules/${id}`),
  
  getRestoreHistory: () => api.get<RestoreHistory[]>('/backup/restore/history'),
  
  executeRestore: (data: { backup_file_id: number; target_environment: string }) => 
    api.post<{ id: number; message: string }>('/backup/restore/execute', data),
  
  getStorageProviders: () => api.get<StorageProvider[]>('/backup/storage-providers'),
  
  updateStorageProvider: (id: number, data: any) => api.put<StorageProvider>(`/backup/storage-providers/${id}`, data),
  
  getDrPlans: () => api.get<DrPlan[]>('/backup/dr-plans'),
  
  getDrPlan: (id: number) => api.get<DrPlan>(`/backup/dr-plans/${id}`),
  
  updateDrPlan: (id: number, data: any) => api.put<DrPlan>(`/backup/dr-plans/${id}`, data),
};
