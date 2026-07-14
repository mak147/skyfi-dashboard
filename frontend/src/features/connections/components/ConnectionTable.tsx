import { useNavigate } from 'react-router-dom';
import { clsx } from 'clsx';
import { Button } from '@/components/ui/button';
import type { Connection } from '../types';
import { ConnectionStatusBadge } from './ConnectionStatusBadge';

interface ConnectionTableProps {
  connections: Connection[];
  isLoading?: boolean;
  sort: string;
  onSortChange: (sort: string) => void;
  onDelete?: (connection: Connection) => void;
  canUpdate?: boolean;
  canDelete?: boolean;
}

const SortIcon = ({ active, desc }: { active: boolean; desc: boolean }) => (
  <span className={clsx('ml-1 text-xs', active ? 'text-indigo-600' : 'text-slate-300')}>
    {desc ? '▼' : '▲'}
  </span>
);

export const ConnectionTable = ({
  connections,
  isLoading,
  sort,
  onSortChange,
  onDelete,
  canUpdate,
  canDelete,
}: ConnectionTableProps) => {
  const navigate = useNavigate();

  const handleSort = (field: string) => {
    const isDesc = sort.startsWith('-') && sort.slice(1) === field;
    onSortChange(isDesc ? field : `-${field}`);
  };

  const renderHeader = (label: string, field: string) => {
    const active = sort === field || sort === `-${field}`;
    const desc = sort === `-${field}`;

    return (
      <th
        className="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-700"
        onClick={() => handleSort(field)}
      >
        <span className="flex items-center">
          {label}
          <SortIcon active={active} desc={desc} />
        </span>
      </th>
    );
  };

  if (isLoading) {
    return (
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div className="animate-pulse">
          <div className="h-12 bg-slate-50" />
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-14 border-t border-slate-100 bg-white" />
          ))}
        </div>
      </div>
    );
  }

  if (connections.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 shadow-sm">
        <p className="text-sm text-slate-400">No connections found.</p>
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full">
          <thead className="bg-slate-50">
            <tr>
              {renderHeader('ID', 'connection_number')}
              {renderHeader('Name', 'name')}
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Package</th>
              {renderHeader('Type', 'type')}
              {renderHeader('Status', 'status')}
              <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {connections.map((connection) => (
              <tr
                key={connection.id}
                className="group cursor-pointer transition hover:bg-slate-50"
                onClick={() => navigate(`/connections/${connection.id}`)}
              >
                <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">
                  {connection.connection_number}
                </td>
                <td className="px-4 py-3 text-sm text-slate-700">
                  {connection.name}
                </td>
                <td className="px-4 py-3 text-sm text-slate-600">
                  {connection.customer_name}
                </td>
                <td className="px-4 py-3 text-sm text-slate-600">
                  {connection.package_name}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 capitalize">
                  {connection.type.replace('_', ' ')}
                </td>
                <td className="whitespace-nowrap px-4 py-3">
                  <ConnectionStatusBadge status={connection.status} />
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right">
                  <div className="flex items-center justify-end gap-2 opacity-0 transition group-hover:opacity-100">
                    {canUpdate && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          navigate(`/connections/${connection.id}/edit`);
                        }}
                      >
                        Edit
                      </Button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};
