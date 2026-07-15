import React from 'react';
import { HardDrive, Cloud, Server, Database, Settings } from 'lucide-react';
import type { StorageProvider } from '../types';

export const StorageCard = ({ provider, onEdit }: { provider: StorageProvider; onEdit: (p: StorageProvider) => void }) => {
  const getIcon = (type: string) => {
    switch (type) {
      case 'local': return HardDrive;
      case 's3': return Cloud;
      case 'ftp':
      case 'sftp': return Server;
      case 'nas': return Database;
      default: return HardDrive;
    }
  };

  const Icon = getIcon(provider.type);

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="flex items-start justify-between">
        <div className={`rounded-lg p-3 ${provider.is_active ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-50 text-slate-400'}`}>
          <Icon className="h-6 w-6" />
        </div>
        <button 
          onClick={() => onEdit(provider)}
          className="rounded-lg p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors"
        >
          <Settings className="h-5 w-5" />
        </button>
      </div>
      
      <div className="mt-4">
        <div className="flex items-center space-x-2">
          <h3 className="text-lg font-bold text-slate-900">{provider.name}</h3>
          {provider.is_default && (
            <span className="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">
              Default
            </span>
          )}
        </div>
        <p className="text-sm text-slate-500 uppercase tracking-tighter">{provider.type}</p>
      </div>

      <div className="mt-6 space-y-2">
        {Object.entries(provider.config).map(([key, value]) => (
          <div key={key} className="flex justify-between text-xs">
            <span className="text-slate-400 capitalize">{key.replace('_', ' ')}</span>
            <span className="font-mono text-slate-600 truncate max-w-[120px]">{String(value)}</span>
          </div>
        ))}
      </div>

      <div className="mt-6 flex items-center justify-between">
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
          provider.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`}>
          {provider.is_active ? 'Connected' : 'Disconnected'}
        </span>
      </div>
    </div>
  );
};
