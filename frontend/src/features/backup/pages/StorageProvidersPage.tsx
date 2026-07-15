import React, { useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import { backupApi } from '../api/backupApi';
import { StorageCard } from '../components/StorageCard';
import type { StorageProvider } from '../types';

export const StorageProvidersPage = () => {
  const [providers, setProviders] = useState<StorageProvider[]>([]);
  const [, setLoading] = useState(true);

  useEffect(() => {
    backupApi.getStorageProviders().then((res: { data: StorageProvider[] }) => {
      setProviders(res.data);
      setLoading(false);
    });
  }, []);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Storage Providers</h1>
          <p className="text-slate-500">Configure where your backup files are stored off-site.</p>
        </div>
        <button className="flex items-center space-x-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">
          <Plus className="h-4 w-4" />
          <span>Add Provider</span>
        </button>
      </div>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        {providers.map((provider) => (
          <StorageCard 
            key={provider.id} 
            provider={provider} 
            onEdit={(p) => console.log('Edit provider', p)} 
          />
        ))}
      </div>
    </div>
  );
};
