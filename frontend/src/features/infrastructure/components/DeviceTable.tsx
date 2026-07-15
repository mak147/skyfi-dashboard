import { useNavigate } from 'react-router-dom';
import { clsx } from 'clsx';
import { Button } from '@/components/ui/button';
import type { NetworkDevice } from '../types';
import { StatusBadge } from './StatusBadge';

interface DeviceTableProps {
  devices: NetworkDevice[];
  isLoading?: boolean;
  sort: string;
  onSortChange: (sort: string) => void;
  onDelete?: (device: NetworkDevice) => void;
  canUpdate?: boolean;
  canDelete?: boolean;
}

const SortIcon = ({ active, desc }: { active: boolean; desc: boolean }) => (
  <span className={clsx('ml-1 text-xs', active ? 'text-indigo-600' : 'text-slate-300')}>
    {desc ? '▼' : '▲'}
  </span>
);

const getTypeIcon = (type: string) => {
  const icons: Record<string, string> = {
    router: '🖧',
    switch: '🔀',
    radio: '📻',
    access_point: '📡',
    olt: '🔗',
    onu: '🔌',
    ups: '🔋',
    other: '📦',
  };
  return icons[type] || '📦';
};

export const DeviceTable = ({
  devices,
  isLoading,
  sort,
  onSortChange,
  onDelete,
  canUpdate,
  canDelete,
}: DeviceTableProps) => {
  const navigate = useNavigate();

  const handleSort = (field: string) => {
    const currentField = sort.startsWith('-') ? sort.slice(1) : sort;
    const isDesc = sort.startsWith('-');

    if (currentField === field) {
      onSortChange(isDesc ? field : `-${field}`);
    } else {
      onSortChange(`-${field}`);
    }
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

  if (devices.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 shadow-sm">
        <p className="text-sm text-slate-400">No devices found.</p>
        <p className="mt-1 text-xs text-slate-400">Try adjusting your filters or search query.</p>
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full">
          <thead className="bg-slate-50">
            <tr>
              {renderHeader('Type', 'device_type')}
              {renderHeader('Name', 'name')}
              {renderHeader('Vendor', 'vendor')}
              {renderHeader('Model', 'model')}
              {renderHeader('Serial', 'serial_number')}
              {renderHeader('IP Address', 'ip_address')}
              {renderHeader('Location', 'tower_name')}
              {renderHeader('Status', 'status')}
              <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {devices.map((device) => (
              <tr
                key={device.id}
                className="group cursor-pointer transition hover:bg-slate-50"
                onClick={() => navigate(`/network/infrastructure/devices/${device.id}`)}
              >
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                  <span className="flex items-center gap-1.5">
                    <span>{getTypeIcon(device.device_type)}</span>
                    <span className="capitalize">{device.device_type.replace('_', ' ')}</span>
                  </span>
                </td>
                <td className="px-4 py-3 text-sm text-slate-700">
                  <div className="font-medium">{device.name}</div>
                  {device.model && <div className="text-xs text-slate-400">{device.model}</div>}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{device.vendor || '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{device.model || '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 font-mono">{device.serial_number || '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 font-mono">{device.ip_address || '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                  {device.tower_name || device.pop_site_name || '-'}
                  {device.location_description && <div className="text-xs text-slate-400">{device.location_description}</div>}
                </td>
                <td className="whitespace-nowrap px-4 py-3">
                  <StatusBadge status={device.status} type="device" />
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right">
                  <div className="flex items-center justify-end gap-2 opacity-0 transition group-hover:opacity-100">
                    {canUpdate && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          navigate(`/network/infrastructure/devices/${device.id}/edit`);
                        }}
                      >
                        Edit
                      </Button>
                    )}
                    {canDelete && (
                      <Button
                        variant="danger"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          onDelete?.(device);
                        }}
                      >
                        Delete
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
