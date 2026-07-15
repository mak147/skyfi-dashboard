import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { DepartmentForm } from '../components/DepartmentForm';
import { DepartmentTable } from '../components/DepartmentTable';
import { SystemPageSkeleton } from '../components/SystemPageSkeleton';
import { useDeleteDepartment, useDepartments, useSaveDepartment, useToggleDepartmentStatus } from '../api/useSystem';
import type { Department } from '../types';
export const DepartmentsPage = () => { const [editing, setEditing] = useState<Department | null>(null); const q = useDepartments(); const save = useSaveDepartment(editing?.id); const del = useDeleteDepartment(); const toggle = useToggleDepartmentStatus(); if (q.isLoading) return <SystemPageSkeleton />; if (q.error || !q.data) return <Alert title="Departments unavailable">{apiErrorMessage(q.error)}</Alert>; return <div className="space-y-6"><h1 className="text-2xl font-bold dark:text-white">Departments</h1><DepartmentForm department={editing} isSaving={save.isPending} onSave={(data) => save.mutate(data, { onSuccess: () => setEditing(null) })} /><DepartmentTable departments={q.data} onEdit={setEditing} onDelete={(id) => del.mutate(id)} onToggle={(d) => toggle.mutate({ id: d.id, status: d.status === 'active' ? 'inactive' : 'active' })} /></div>; };
