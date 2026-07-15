export interface BackupJob {
  id: number;
  schedule_id: number | null;
  type: 'database' | 'files' | 'config' | 'full';
  status: 'pending' | 'running' | 'completed' | 'failed';
  started_at: string | null;
  finished_at: string | null;
  error_message: string | null;
  created_at: string;
  updated_at: string;
  schedule_name?: string;
}

export interface BackupFile {
  id: number;
  job_id: number;
  storage_provider_id: number;
  file_path: string;
  file_size: number;
  checksum: string;
  metadata: Record<string, unknown>;
  verified_at: string | null;
  expires_at: string | null;
  created_at: string;
  updated_at: string;
  storage_provider_name?: string;
}

export interface BackupSchedule {
  id: number;
  name: string;
  type: 'database' | 'files' | 'config' | 'full';
  cron_expression: string;
  retention_days: number;
  storage_provider_id: number;
  is_active: boolean;
  last_run_at: string | null;
  next_run_at: string | null;
  created_at: string;
  updated_at: string;
  storage_provider_name?: string;
}

export interface StorageProvider {
  id: number;
  name: string;
  type: 'local' | 's3' | 'ftp' | 'sftp' | 'nas';
  config: Record<string, unknown>;
  is_active: boolean;
  is_default: boolean;
}

export interface RestoreHistory {
  id: number;
  backup_file_id: number;
  status: 'pending' | 'running' | 'completed' | 'failed' | 'rolled_back';
  target_environment: string;
  started_at: string | null;
  finished_at: string | null;
  error_message: string | null;
  created_at: string;
  backup_file_name?: string;
}

export interface DrPlan {
  id: number;
  name: string;
  description: string | null;
  rpo_minutes: number;
  rto_minutes: number;
  content: string;
  created_at: string;
  updated_at: string;
}

export interface BackupStatistics {
  total_jobs: number;
  successful_jobs: number;
  failed_jobs: number;
  total_files: number;
  total_size: number;
}
