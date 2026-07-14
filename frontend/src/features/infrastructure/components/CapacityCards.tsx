import { clsx } from 'clsx';

interface CapacityCardsProps {
  totalPopSites: number;
  activePopSites: number;
  totalTowers: number;
  activeTowers: number;
  totalSectors: number;
  activeSectors: number;
  totalDevices: number;
  activeDevices: number;
  offlineDevices: number;
  maintenanceDevices: number;
}

const cards = [
  { label: 'POP Sites', value: 0, active: 0, color: 'indigo', icon: '📍' },
  { label: 'Towers', value: 0, active: 0, color: 'blue', icon: '🗼' },
  { label: 'Sectors', value: 0, active: 0, color: 'emerald', icon: '📡' },
  { label: 'Devices', value: 0, active: 0, color: 'purple', icon: '🖧' },
] as const;

export const CapacityCards = ({
  totalPopSites,
  activePopSites,
  totalTowers,
  activeTowers,
  totalSectors,
  activeSectors,
  totalDevices,
  activeDevices,
  offlineDevices,
  maintenanceDevices,
}: CapacityCardsProps) => {
  const data = [
    { label: 'POP Sites', value: totalPopSites, active: activePopSites, color: 'indigo', icon: '📍' },
    { label: 'Towers', value: totalTowers, active: activeTowers, color: 'blue', icon: '🗼' },
    { label: 'Sectors', value: totalSectors, active: activeSectors, color: 'emerald', icon: '📡' },
    { label: 'Devices', value: totalDevices, active: activeDevices, color: 'purple', icon: '🖧' },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {data.map((card) => (
        <div
          key={card.label}
          className={clsx(
            'rounded-xl border bg-white p-5 shadow-sm transition hover:shadow-md',
            `border-${card.color}-200`
          )}
        >
          <div className="flex items-start justify-between">
            <div>
              <p className="text-sm font-medium text-slate-500">{card.label}</p>
              <div className="mt-2 flex items-baseline gap-2">
                <span className="text-3xl font-bold text-slate-900">{card.value}</span>
                <span className={clsx('text-sm font-medium', `text-${card.color}-600`)}>
                  {card.active} active
                </span>
              </div>
            </div>
            <span className="text-4xl opacity-60">{card.icon}</span>
          </div>
        </div>
      ))}

      <div className="lg:col-span-4">
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-slate-700 mb-4">Device Status Breakdown</h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center p-3 rounded-lg bg-emerald-50">
              <p className="text-2xl font-bold text-emerald-700">{activeDevices}</p>
              <p className="text-xs text-emerald-600">Deployed</p>
            </div>
            <div className="text-center p-3 rounded-lg bg-amber-50">
              <p className="text-2xl font-bold text-amber-700">{maintenanceDevices}</p>
              <p className="text-xs text-amber-600">Maintenance</p>
            </div>
            <div className="text-center p-3 rounded-lg bg-red-50">
              <p className="text-2xl font-bold text-red-700">{offlineDevices}</p>
              <p className="text-xs text-red-600">Offline</p>
            </div>
            <div className="text-center p-3 rounded-lg bg-slate-50">
              <p className="text-2xl font-bold text-slate-700">
                {totalDevices - activeDevices - maintenanceDevices - offlineDevices}
              </p>
              <p className="text-xs text-slate-600">Other</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
