import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
export const SettingsForm = ({ children, onSubmit, isSaving = false }: { children: ReactNode; onSubmit: () => void; isSaving?: boolean }) => { const submit = (event: FormEvent) => { event.preventDefault(); onSubmit(); }; return <form className="space-y-4" onSubmit={submit}>{children}<Button type="submit" isLoading={isSaving}>Save changes</Button></form>; };
