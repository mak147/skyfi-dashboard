import { useNavigate } from 'react-router-dom';
import { clsx } from 'clsx';
import { Button } from '@/components/ui/button';
import type { Sector } from '../types';
import { StatusBadge } from './StatusBadge';

interface SectorTableProps {
  sectors: Sector[];
  isLoading?: boolean;
  sort: string;
  onSortChange: (sort: string) => void;
  onDelete?: (sector: Sector) => void;
  canUpdate?: boolean;
  canDelete?: boolean;
}

const SortIcon = ({ active, desc }: { active: boolean; desc: boolean }) => (
  <span className={clsx('ml-1 text-xs', active ? 'text-indigo-600' : 'text-slate-300')}>
    {desc ? '▼' : '▲'}
  </span>
);

export const SectorTable = ({
  sectors,
  isLoading,
  sort,
  onSortChange,
  onDelete,
  canUpdate,
  canDelete,
}: SectorTableProps) => {
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

  if (sectors.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 shadow-sm">
        <p className="text-sm text-slate-400">No sectors found.</p>
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
              {renderHeader('Name', 'name')}
              {renderHeader('Azimuth', 'azimuth')}
              {renderHeader('Beamwidth', 'beamwidth')}
              {renderHeader('Freq (MHz)', 'frequency_mhz')}
              {renderHeader('Channel', 'channel_width_mhz')}
              {renderHeader('Device', 'device_name')}
              {renderHeader('Capacity', 'capacity_mbps')}
              {renderHeader('Status', 'status')}
              <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {sectors.map((sector) => (
              <tr
                key={sector.id}
                className="group cursor-pointer transition hover:bg-slate-50"
                onClick={() => navigate(`/network/infrastructure/sectors/${sector.id}`)}
              >
                <td className="px-4 py-3 text-sm text-slate-700">
                  <div className="font-medium">{sector.name}</div>
                  {sector.ssid && <div className="text-xs text-slate-400">SSID: {sector.ssid}</div>}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{sector.azimuth}°</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{sector.beamwidth ? `${sector.beamwidth}°` : '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{sector.frequency_mhz}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{sector.channel_width_mhz ? `${sector.channel_width_mhz} MHz` : '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{sector.device_name || '-'}</td>
                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                  {sector.capacity_mbps ? `${sector.capacity_mbps} Mbps` : '-'}
                  {sector.connection_count !== undefined && sector.capacity_mbps && (
                    <div className="text-xs text-slate-400">
                      {sector.connection_count} / {sector.max_subscribers || '∞'} subs
                    </div>
                  )}
                </td>
                <td className="whitespace-nowrap px-4 py-3">
                  <StatusBadge status={sector.status} type="sector" />
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right">
                  <div className="flex items-center justify-end gap-2 opacity-0 transition group-hover:opacity-100">
                    {canUpdate && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          navigate(`/network/infrastructure/sectors/${sector.id}/edit`);
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
                          onDelete?.(sector);
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
