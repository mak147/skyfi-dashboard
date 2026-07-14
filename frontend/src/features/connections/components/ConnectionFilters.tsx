import React from 'react';
import { ConnectionFilters, ConnectionStatus, ConnectionType } from '../types';

interface ConnectionFiltersBarProps {
  filters: ConnectionFilters;
  onChange: (filters: ConnectionFilters) => void;
}

export const ConnectionFiltersBar: React.FC<ConnectionFiltersBarProps> = ({ filters, onChange }) => {
  const handleFilterChange = (name: string, value: string) => {
    onChange({ ...filters, [name]: value });
  };

  return (
    <div className="flex flex-wrap gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div className="flex-1 min-w-[200px]">
        <input
          type="text"
          placeholder="Search connections..."
          className="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          value={filters.search || ''}
          onChange={(e) => handleFilterChange('search', e.target.value)}
        />
      </div>
      <select
        className="rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        value={filters.status || ''}
        onChange={(e) => handleFilterChange('status', e.target.value)}
      >
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
        <option value="disconnected">Disconnected</option>
      </select>
      <select
        className="rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        value={filters.type || ''}
        onChange={(e) => handleFilterChange('type', e.target.value)}
      >
        <option value="">All Types</option>
        <option value="pppoe">PPPoE</option>
        <option value="hotspot">Hotspot</option>
        <option value="static_ip">Static IP</option>
      </select>
    </div>
  );
};
