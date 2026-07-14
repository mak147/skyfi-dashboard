import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { getConnections } from '@/features/connections/api/connectionApi';
import { getCustomers } from '@/features/customers/api/customerApi';
import { getPackages } from '@/features/packages/api/packageApi';
import { apiErrorMessage } from '@/lib/apiClient';

import { importPppoeUsers } from '../api/usePppoe';
import { RouterSelector } from '../components/RouterSelector';

export const ImportUsersPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [routerId, setRouterId] = useState<number | ''>('');
  const [defaultCustomerId, setDefaultCustomerId] = useState<number | ''>('');
  const [defaultConnectionId, setDefaultConnectionId] = useState<number | ''>('');
  const [defaultPackageId, setDefaultPackageId] = useState<number | ''>('');
  const [usernamesText, setUsernamesText] = useState('');
  const [overwriteConflicts, setOverwriteConflicts] = useState(false);
  const [importResult, setImportResult] = useState<{ imported_count: number; failed_count: number; errors: string[] } | null>(null);

  const customersQuery = useQuery({ queryKey: ['customers', 'selector'], queryFn: () => getCustomers(1, 100, {}, 'full_name') });
  const connectionsQuery = useQuery({ queryKey: ['connections', 'selector'], queryFn: () => getConnections(1, 100, {}, 'connection_number') });
  const packagesQuery = useQuery({ queryKey: ['packages', 'selector'], queryFn: () => getPackages(1, 100, {}, 'name') });

  const importMutation = useMutation({
    mutationFn: importPppoeUsers,
    onSuccess: (res) => {
      setImportResult(res);
      queryClient.invalidateQueries({ queryKey: ['pppoe'] });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!routerId) return;

    const usernames = usernamesText
      .split('\n')
      .map((u) => u.trim())
      .filter(Boolean);

    importMutation.mutate({
      router_id: Number(routerId),
      usernames: usernames.length > 0 ? usernames : undefined,
      default_customer_id: defaultCustomerId ? Number(defaultCustomerId) : undefined,
      default_connection_id: defaultConnectionId ? Number(defaultConnectionId) : undefined,
      default_package_id: defaultPackageId ? Number(defaultPackageId) : undefined,
      overwrite_conflicts: overwriteConflicts,
    });
  };

  const customers = customersQuery.data?.data.map((item) => item.attributes) ?? [];
  const connections = connectionsQuery.data?.data.map((item) => item.attributes) ?? [];
  const packages = packagesQuery.data?.data.map((item) => item.attributes) ?? [];

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Import Existing PPPoE Users</h1>
          <p className="mt-1 text-sm text-slate-500">
            Fetch physical <code className="text-indigo-600">/ppp/secret/print</code> secrets from a MikroTik router and enroll them into the SkyFi database.
          </p>
        </div>
        <Button variant="secondary" onClick={() => navigate('/network/pppoe/sync')}>
          Back to Sync
        </Button>
      </div>

      {importMutation.error ? (
        <Alert title="Import Failed" variant="danger">
          {apiErrorMessage(importMutation.error)}
        </Alert>
      ) : null}

      {importResult ? (
        <Alert
          title="Import Completed"
          variant={importResult.failed_count === 0 ? 'success' : 'info'}
        >
          <p>
            Successfully imported <strong className="font-semibold">{importResult.imported_count}</strong> user(s). Failed: <strong className="font-semibold">{importResult.failed_count}</strong>.
          </p>
          {importResult.errors.length > 0 ? (
            <ul className="mt-2 list-disc pl-4 text-xs space-y-1 max-h-32 overflow-y-auto">
              {importResult.errors.map((err, idx) => (
                <li key={idx}>{err}</li>
              ))}
            </ul>
          ) : null}
        </Alert>
      ) : null}

      <form onSubmit={handleSubmit} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-6">
        <div>
          <label className="block text-sm font-medium text-slate-700">Source MikroTik Router</label>
          <div className="mt-2">
            <RouterSelector value={routerId} onChange={setRouterId} />
          </div>
          <p className="mt-1 text-xs text-slate-500">All /ppp/secret entries on this device will be scanned during import.</p>
        </div>

        <div className="grid gap-5 md:grid-cols-3">
          <div>
            <label className="block text-sm font-medium text-slate-700">Default Customer Mapping</label>
            <select
              value={defaultCustomerId}
              onChange={(e) => setDefaultCustomerId(e.target.value ? Number(e.target.value) : '')}
              className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="">Select Default Customer...</option>
              {customers.map((c) => (
                <option key={c.id} value={c.id}>{c.full_name}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700">Default Connection ID</label>
            <select
              value={defaultConnectionId}
              onChange={(e) => setDefaultConnectionId(e.target.value ? Number(e.target.value) : '')}
              className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="">Select Default Connection...</option>
              {connections.map((c) => (
                <option key={c.id} value={c.id}>{c.connection_number}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700">Default Internet Package</label>
            <select
              value={defaultPackageId}
              onChange={(e) => setDefaultPackageId(e.target.value ? Number(e.target.value) : '')}
              className="mt-2 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="">Select Default Package...</option>
              {packages.map((p) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-700">Target Usernames (Optional Filter)</label>
          <textarea
            rows={4}
            value={usernamesText}
            onChange={(e) => setUsernamesText(e.target.value)}
            placeholder="Type specific usernames separated by newlines (leave blank to import all secrets from the router)..."
            className="mt-2 w-full rounded-md border border-slate-300 p-3 text-sm font-mono focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        <div className="flex items-center gap-2">
          <input
            type="checkbox"
            id="overwrite"
            checked={overwriteConflicts}
            onChange={(e) => setOverwriteConflicts(e.target.checked)}
            className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
          />
          <label htmlFor="overwrite" className="text-sm text-slate-700">
            Overwrite profile and status if account already exists in SkyFi database
          </label>
        </div>

        <div className="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
          <Button type="button" variant="secondary" onClick={() => navigate('/network/pppoe/sync')}>
            Cancel
          </Button>
          <Button type="submit" disabled={!routerId || importMutation.isPending}>
            {importMutation.isPending ? 'Importing Users...' : 'Execute Import'}
          </Button>
        </div>
      </form>
    </div>
  );
};
