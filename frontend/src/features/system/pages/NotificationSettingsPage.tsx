import { useEffect, useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { NotificationPreferenceForm } from '../components/NotificationPreferenceForm';
import { SettingsForm } from '../components/SettingsForm';
import { SystemPageSkeleton } from '../components/SystemPageSkeleton';
import { useNotificationSettings, useUpdateNotificationSettings } from '../api/useSystem';
import type { NotificationSettings } from '../types';
export const NotificationSettingsPage = () => { const q = useNotificationSettings(); const save = useUpdateNotificationSettings(); const [form, setForm] = useState<NotificationSettings | null>(null); useEffect(() => { if (q.data) setForm(q.data); }, [q.data]); if (q.isLoading || !form) return <SystemPageSkeleton />; if (q.error || !q.data) return <Alert title="Notification settings unavailable">{apiErrorMessage(q.error)}</Alert>; return <div className="space-y-6"><h1 className="text-2xl font-bold dark:text-white">Notification Settings</h1><SettingsForm onSubmit={() => save.mutate(form)} isSaving={save.isPending}><NotificationPreferenceForm value={form} onChange={setForm} /><p className="text-sm text-slate-500">SMS provider and MFA delivery remain configuration placeholders for future integration.</p></SettingsForm></div>; };
