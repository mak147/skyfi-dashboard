import { clsx } from 'clsx';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Tower, TowerType, OwnerType } from '../types';

interface TowerFormProps {
  initialData?: Partial<Tower>;
  popSites: Array<{ id: number; name: string }>;
  onSubmit: (data: Partial<Tower>) => void;
  isSubmitting?: boolean;
  submitLabel?: string;
}

const TOWER_TYPES: { value: TowerType; label: string }[] = [
  { value: 'lattice', label: 'Lattice Tower' },
  { value: 'monopole', label: 'Monopole' },
  { value: 'guyed', label: 'Guyed Tower' },
  { value: 'building', label: 'Building/Rooftop' },
  { value: 'water_tank', label: 'Water Tank' },
  { value: 'other', label: 'Other' },
];

const OWNER_TYPES: { value: OwnerType; label: string }[] = [
  { value: 'owned', label: 'Owned' },
  { value: 'leased', label: 'Leased' },
  { value: 'shared', label: 'Shared' },
  { value: 'managed', label: 'Managed' },
];

const STATUSES: { value: Tower['status']; label: string }[] = [
  { value: 'planning', label: 'Planning' },
  { value: 'active', label: 'Active' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'decommissioned', label: 'Decommissioned' },
];

export const TowerForm = ({
  initialData = {},
  popSites,
  onSubmit,
  isSubmitting,
  submitLabel = 'Save',
}: TowerFormProps) => {
  const [formData, setFormData] = useState<Partial<Tower>>({
    pop_site_id: null,
    name: '',
    code: '',
    tower_type: 'lattice',
    height_meters: '',
    owner: 'owned',
    address_line1: '',
    city: '',
    region: '',
    gps_latitude: '',
    gps_longitude: '',
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

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <Label htmlFor="pop_site_id" className="block text-sm font-medium text-slate-700">
            POP Site *
          </Label>
          <Select value={String(formData.pop_site_id || '')} onValueChange={(v) => handleChange('pop_site_id', v ? Number(v) : null)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select POP site" />
            </SelectTrigger>
            <SelectContent>
              {popSites.map((site) => (
                <SelectItem key={site.id} value={String(site.id)}>
                  {site.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="name" className="block text-sm font-medium text-slate-700">
            Tower Name *
          </Label>
          <Input
            id="name"
            value={formData.name || ''}
            onChange={(e) => handleChange('name', e.target.value)}
            placeholder="Enter tower name"
            required
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="code" className="block text-sm font-medium text-slate-700">
            Code
          </Label>
          <Input
            id="code"
            value={formData.code || ''}
            onChange={(e) => handleChange('code', e.target.value.toUpperCase())}
            placeholder="TWR001"
            className="mt-1"
            maxLength={50}
          />
        </div>

        <div>
          <Label htmlFor="tower_type" className="block text-sm font-medium text-slate-700">
            Tower Type *
          </Label>
          <Select value={formData.tower_type || 'lattice'} onValueChange={(v) => handleChange('tower_type', v)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select tower type" />
            </SelectTrigger>
            <SelectContent>
              {TOWER_TYPES.map((t) => (
                <SelectItem key={t.value} value={t.value}>
                  {t.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="height_meters" className="block text-sm font-medium text-slate-700">
            Height (meters)
          </Label>
          <Input
            id="height_meters"
            type="number"
            step="0.1"
            min="0"
            max="1000"
            value={formData.height_meters || ''}
            onChange={(e) => handleChange('height_meters', e.target.value)}
            placeholder="e.g., 45.5"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="owner" className="block text-sm font-medium text-slate-700">
            Ownership *
          </Label>
          <Select value={formData.owner || 'owned'} onValueChange={(v) => handleChange('owner', v)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select ownership" />
            </SelectTrigger>
            <SelectContent>
              {OWNER_TYPES.map((o) => (
                <SelectItem key={o.value} value={o.value}>
                  {o.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
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
          <Label htmlFor="address_line1" className="block text-sm font-medium text-slate-700">
            Address
          </Label>
          <Input
            id="address_line1"
            value={formData.address_line1 || ''}
            onChange={(e) => handleChange('address_line1', e.target.value)}
            placeholder="Tower location address"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="city" className="block text-sm font-medium text-slate-700">
            City
          </Label>
          <Input
            id="city"
            value={formData.city || ''}
            onChange={(e) => handleChange('city', e.target.value)}
            placeholder="City"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="region" className="block text-sm font-medium text-slate-700">
            Region/State
          </Label>
          <Input
            id="region"
            value={formData.region || ''}
            onChange={(e) => handleChange('region', e.target.value)}
            placeholder="Region/State"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="gps_latitude" className="block text-sm font-medium text-slate-700">
            GPS Latitude
          </Label>
          <Input
            id="gps_latitude"
            type="number"
            step="0.0000001"
            value={formData.gps_latitude || ''}
            onChange={(e) => handleChange('gps_latitude', e.target.value)}
            placeholder="31.5204"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="gps_longitude" className="block text-sm font-medium text-slate-700">
            GPS Longitude
          </Label>
          <Input
            id="gps_longitude"
            type="number"
            step="0.0000001"
            value={formData.gps_longitude || ''}
            onChange={(e) => handleChange('gps_longitude', e.target.value)}
            placeholder="74.3587"
            className="mt-1"
          />
        </div>

        <div className="sm:col-span-2">
          <Label htmlFor="notes" className="block text-sm font-medium text-slate-700">
            Notes
          </Label>
          <Textarea
            id="notes"
            value={formData.notes || ''}
            onChange={(e) => handleChange('notes', e.target.value)}
            placeholder="Additional notes about this tower..."
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

import { useState } from 'react';
