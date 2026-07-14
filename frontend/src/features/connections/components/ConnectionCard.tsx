import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import type { Connection } from '../types';
import { ConnectionStatusBadge } from './ConnectionStatusBadge';

interface ConnectionCardProps {
  connection: Connection;
}

export const ConnectionCard: React.FC<ConnectionCardProps> = ({ connection }) => {
  const navigate = useNavigate();

  return (
    <div 
      className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm active:bg-slate-50"
      onClick={() => navigate(`/connections/${connection.id}`)}
    >
      <div className="flex items-center justify-between">
        <span className="text-xs font-bold text-indigo-600">{connection.connection_number}</span>
        <ConnectionStatusBadge status={connection.status} />
      </div>
      <div className="mt-2">
        <h3 className="font-medium text-slate-900">{connection.name}</h3>
        <p className="text-sm text-slate-500">{connection.customer_name}</p>
      </div>
      <div className="mt-3 flex items-center justify-between border-t border-slate-50 pt-3 text-xs text-slate-400">
        <span className="capitalize">{connection.type.replace('_', ' ')}</span>
        <span>{connection.package_name}</span>
      </div>
    </div>
  );
};
