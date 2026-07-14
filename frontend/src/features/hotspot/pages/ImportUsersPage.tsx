import { useNavigate } from 'react-router-dom';
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { apiErrorMessage } from '@/lib/apiClient';

import { importHotspotUsers } from '../api/useHotspot';

export const ImportUsersPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [routerId, setRouterId] = useState('');
  const [overwrite, setOverwrite] = useState(false);

  const importMutation = useMutation({
    mutationFn: (data: Parameters<typeof importHotspotUsers>[0]) => importHotspotUsers(data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['hotspot'] }),
  });

  const handleImport = () => {
    if (!routerId) return;
    importMutation.mutate({
      router_id: Number(routerId),
      overwrite_conflicts: overwrite,
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Import Hotspot Users</h1>
          <p className="mt-1 text-sm text-slate-500">Import existing hotspot users from a MikroTik router into SkyFi database.</p>
        </div>
        <Button variant="secondary" onClick={() => navigate('/hotspot')}>Cancel</Button>
      </div>

      {importMutation.error ? (
        <Alert title="Import failed" variant="danger">{apiErrorMessage(importMutation.error)}</Alert>
      ) : null}

      {importMutation.isSuccess && importMutation.data ? (
        <div className="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
          <p className="text-sm font-semibold text-indigo-800">Import Complete</p>
          <p className="text-sm text-indigo-700">Imported: {importMutation.data.imported_count} | Failed: {importMutation.data.failed_count}</p>
          {importMutation.data.errors.length > 0 ? (
            <ul className="mt-2 text-xs text-indigo-600 space-y-1">
              {importMutation.data.errors.slice(0, 10).map((e, i) => <li key={i}>• {e}</li>)}
            </ul>
          ) : null}
        </div>
      ) : null}

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div>
          <label className="block text-sm font-semibold text-slate-700">Router ID *</label>
          <input
            type="number"
            placeholder="Enter MikroTik router ID..."
            value={routerId}
            onChange={(e) => setRouterId(e.target.value)}
            className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        <label className="flex items-center gap-2">
          <input
            type="checkbox"
            checked={overwrite}
            onChange={(e) => setOverwrite(e.target.checked)}
            className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span className="text-sm text-slate-700">Overwrite conflicts (update existing users with router data)</span>
        </label>

        <div className="flex justify-end gap-2 pt-4 border-t border-slate-200">
          <Button variant="secondary" onClick={() => navigate('/hotspot')}>Cancel</Button>
          <Button onClick={handleImport} disabled={!routerId || importMutation.isPending}>
            {importMutation.isPending ? 'Importing...' : 'Import from Router'}
          </Button>
        </div>
      </div>
    </div>
  );
};
