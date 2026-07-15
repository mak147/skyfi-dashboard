import { RouteObject } from 'react-router-dom';
import { BackupDashboard } from './pages/BackupDashboard';
import { BackupJobsPage } from './pages/BackupJobsPage';
import { RestoreCenterPage } from './pages/RestoreCenterPage';
import { SchedulesPage } from './pages/SchedulesPage';
import { StorageProvidersPage } from './pages/StorageProvidersPage';
import { DisasterRecoveryPage } from './pages/DisasterRecoveryPage';

export const backupRoutes: RouteObject[] = [
  { path: 'backup', element: <BackupDashboard /> },
  { path: 'backup/jobs', element: <BackupJobsPage /> },
  { path: 'backup/restore', element: <RestoreCenterPage /> },
  { path: 'backup/schedules', element: <SchedulesPage /> },
  { path: 'backup/storage', element: <StorageProvidersPage /> },
  { path: 'backup/dr', element: <DisasterRecoveryPage /> },
];
