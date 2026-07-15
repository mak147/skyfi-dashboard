import { useState } from 'react';
import { Alert } from '@/components/ui/alert';
import { apiErrorMessage } from '@/lib/apiClient';
import { BranchForm } from '../components/BranchForm';
import { BranchTable } from '../components/BranchTable';
import { SystemPageSkeleton } from '../components/SystemPageSkeleton';
import { useBranches, useDeleteBranch, useSaveBranch, useToggleBranchStatus } from '../api/useSystem';
import type { Branch } from '../types';
export const BranchesPage = () => { const [editing, setEditing] = useState<Branch | null>(null); const q = useBranches(); const save = useSaveBranch(editing?.id); const del = useDeleteBranch(); const toggle = useToggleBranchStatus(); if (q.isLoading) return <SystemPageSkeleton />; if (q.error || !q.data) return <Alert title="Branches unavailable">{apiErrorMessage(q.error)}</Alert>; return <div className="space-y-6"><h1 className="text-2xl font-bold dark:text-white">Branches</h1><BranchForm branch={editing} isSaving={save.isPending} onSave={(data) => save.mutate(data, { onSuccess: () => setEditing(null) })} /><BranchTable branches={q.data} onEdit={setEditing} onDelete={(id) => del.mutate(id)} onToggle={(b) => toggle.mutate({ id: b.id, status: b.status === 'active' ? 'inactive' : 'active' })} /></div>; };
