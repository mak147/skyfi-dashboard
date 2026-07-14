import { useState } from 'react';
import { clsx } from 'clsx';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Sector } from '../types';

interface SectorFormProps {
  initialData?: Partial<Sector>;
  towers: Array<{ id: number; name: string; pop_site_name: string | null }>;
  devices: Array<{ id: number; name: string; device_type: string }>;
  onSubmit: (data: Partial<Sector>) => void;
  isSubmitting?: boolean;
  submitLabel?: string;
}

const STATUSES: { value: Sector['status']; label: string }[] = [
  { value: 'planning', label: 'Planning' },
  { value: 'active', label: 'Active' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'decommissioned', label: 'Decommissioned' },
];

export const SectorForm = ({
  initialData = {},
  towers,
  devices,
  onSubmit,
  isSubmitting,
  submitLabel = 'Save',
}: SectorFormProps) => {
  const [formData, setFormData] = useState<Partial<Sector>>({
    tower_id: null,
    name: '',
    azimuth: 0,
    beamwidth: null,
    frequency_mhz: 0,
    channel_width_mhz: null,
    ssid: '',
    eirp_dbm: null,
    device_id: null,
    capacity_mbps: null,
    max_subscribers: null,
    status: 'planning',
    notes: '',
    ...initialData,
  });

  const handleChange = (field: string, value: string | number | null) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  const apDevices = devices.filter((d) => d.device_type === 'access_point' || d.device_type === 'radio');

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <Label htmlFor="tower_id" className="block text-sm font-medium text-slate-700">
            Tower *
          </Label>
          <Select value={String(formData.tower_id || '')} onValueChange={(v) => handleChange('tower_id', v ? Number(v) : null)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select tower" />
            </SelectTrigger>
            <SelectContent>
              {towers.map((tower) => (
                <SelectItem key={tower.id} value={String(tower.id)}>
                  {tower.name} {tower.pop_site_name && `(${tower.pop_site_name})`}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="name" className="block text-sm font-medium text-slate-700">
            Sector Name *
          </Label>
          <Input
            id="name"
            value={formData.name || ''}
            onChange={(e) => handleChange('name', e.target.value)}
            placeholder="e.g., Sector A, North Sector"
            required
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="azimuth" className="block text-sm font-medium text-slate-700">
            Azimuth (degrees) *
          </Label>
          <Input
            id="azimuth"
            type="number"
            min="0"
            max="359"
            value={formData.azimuth ?? ''}
            onChange={(e) => handleChange('azimuth', e.target.value ? Number(e.target.value) : 0)}
            placeholder="0-359"
            required
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="beamwidth" className="block text-sm font-medium text-slate-700">
            Beamwidth (degrees)
          </Label>
          <Input
            id="beamwidth"
            type="number"
            min="1"
            max="360"
            value={formData.beamwidth ?? ''}
            onChange={(e) => handleChange('beamwidth', e.target.value ? Number(e.target.value) : null)}
            placeholder="e.g., 60, 90, 120"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="frequency_mhz" className="block text-sm font-medium text-slate-700">
            Frequency (MHz) *
          </Label>
          <Input
            id="frequency_mhz"
            type="number"
            min="1"
            value={formData.frequency_mhz ?? ''}
            onChange={(e) => handleChange('frequency_mhz', e.target.value ? Number(e.target.value) : 0)}
            placeholder="e.g., 5810, 2412"
            required
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="channel_width_mhz" className="block text-sm font-medium text-slate-700">
            Channel Width (MHz)
          </Label>
          <Input
            id="channel_width_mhz"
            type="number"
            min="1"
            value={formData.channel_width_mhz ?? ''}
            onChange={(e) => handleChange('channel_width_mhz', e.target.value ? Number(e.target.value) : null)}
            placeholder="20, 40, 80"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="ssid" className="block text-sm font-medium text-slate-700">
            SSID
          </Label>
          <Input
            id="ssid"
            value={formData.ssid || ''}
            onChange={(e) => handleChange('ssid', e.target.value)}
            placeholder="Wireless network name"
            maxLength={64}
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="eirp_dbm" className="block text-sm font-medium text-slate-700">
            EIRP (dBm)
          </Label>
          <Input
            id="eirp_dbm"
            type="number"
            min="-100"
            max="100"
            value={formData.eirp_dbm ?? ''}
            onChange={(e) => handleChange('eirp_dbm', e.target.value ? Number(e.target.value) : null)}
            placeholder="Transmit power"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="device_id" className="block text-sm font-medium text-slate-700">
            Assigned AP/Radio
          </Label>
          <Select value={String(formData.device_id || '')} onValueChange={(v) => handleChange('device_id', v ? Number(v) : null)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select access point or radio" />
            </SelectTrigger>
            <SelectContent>
              {apDevices.map((device) => (
                <SelectItem key={device.id} value={String(device.id)}>
                  {device.name} ({device.device_type.replace('_', ' ')})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="capacity_mbps" className="block text-sm font-medium text-slate-700">
            Capacity (Mbps)
          </Label>
          <Input
            id="capacity_mbps"
            type="number"
            min="1"
            value={formData.capacity_mbps ?? ''}
            onChange={(e) => handleChange('capacity_mbps', e.target.value ? Number(e.target.value) : null)}
            placeholder="e.g., 300, 500, 1000"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="max_subscribers" className="block text-sm font-medium text-slate-700">
            Max Subscribers
          </Label>
          <Input
            id="max_subscribers"
            type="number"
            min="1"
            value={formData.max_subscribers ?? ''}
            onChange={(e) => handleChange('max_subscribers', e.target.value ? Number(e.target.value) : null)}
            placeholder="Maximum concurrent users"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="status" className="block text-sm font-medium text-slate-700">
            Status *
          </Label>
          <Select value={formData.status || 'planning'} onValueChange={(v) => handleChange('status', v)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              {STATUSES.map((s) => (
                <SelectItem key={s.value} value={s.value}>
                  {s.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="sm:col-span-2">
          <Label htmlFor="notes" className="block text-sm font-medium text-slate-700">
            Notes
          </Label>
          <Textarea
            id="notes"
            value={formData.notes || ''}
            onChange={(e) => handleChange('notes', e.target.value)}
            placeholder="Additional notes about this sector..."
            rows={3}
            className="mt-1"
          />
        </div>
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
        <Button type="button" variant="secondary" onClick={() => window.history.back()}>
          Cancel
        </Button>
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving...' : submitLabel}
        </Button>
      </div>
    </form>
  );
};
