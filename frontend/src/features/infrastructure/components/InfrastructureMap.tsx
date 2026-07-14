import { useEffect, useRef } from 'react';

interface InfrastructureMapProps {
  popSites?: Array<{ id: number; name: string; code: string; gps_latitude: string | null; gps_longitude: string | null; status: string }>;
  towers?: Array<{ id: number; name: string; code: string | null; gps_latitude: string | null; gps_longitude: string | null; status: string; pop_site_name: string | null }>;
  className?: string;
}

export const InfrastructureMap = ({ popSites = [], towers = [], className = '' }: InfrastructureMapProps) => {
  const mapRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // Placeholder for Mapbox/Leaflet integration
    // This would initialize a map and add markers for POP sites and towers
    console.log('InfrastructureMap: Would initialize map with', { popSites, towers });
  }, [popSites, towers]);

  const hasCoordinates = popSites.some((p) => p.gps_latitude && p.gps_longitude) ||
    towers.some((t) => t.gps_latitude && t.gps_longitude);

  if (!hasCoordinates) {
    return (
      <div
        ref={mapRef}
        className={clsx('rounded-xl border border-slate-200 bg-slate-50 h-96 flex items-center justify-center', className)}
      >
        <div className="text-center p-8">
          <div className="text-6xl mb-4">🗺️</div>
          <h3 className="text-lg font-medium text-slate-700">Infrastructure Map</h3>
          <p className="mt-2 text-sm text-slate-500">
            Add GPS coordinates to POP sites or towers to visualize them on the map.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div
      ref={mapRef}
      className={clsx('rounded-xl border border-slate-200 bg-slate-100 h-96 relative overflow-hidden', className)}
    >
      <div className="absolute inset-0 flex items-center justify-center">
        <div className="text-center p-8">
          <div className="text-6xl mb-4">🗺️</div>
          <h3 className="text-lg font-medium text-slate-700">Map View (Placeholder)</h3>
          <p className="mt-2 text-sm text-slate-500">
            Integration with Mapbox GL JS or Leaflet would go here.
          </p>
          <div className="mt-4 text-xs text-slate-400 font-mono">
            {popSites.filter((p) => p.gps_latitude).length} POP sites, {towers.filter((t) => t.gps_latitude).length} towers with coordinates
          </div>
        </div>
      </div>
      {/* Map controls placeholder */}
      <div className="absolute top-3 right-3 flex gap-2">
        <button className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50">
          POP Sites
        </button>
        <button className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50">
          Towers
        </button>
        <button className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50">
          Coverage
        </button>
      </div>
    </div>
  );
};

import { clsx } from 'clsx';
