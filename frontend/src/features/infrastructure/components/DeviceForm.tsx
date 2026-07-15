import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { NetworkDevice, DeviceType } from '../types';

interface DeviceFormProps {
  initialData?: Partial<NetworkDevice>;
  popSites: Array<{ id: number; name: string }>;
  towers: Array<{ id: number; name: string; pop_site_id: number }>;
  mikrotikRouters: Array<{ id: number; name: string }>;
  onSubmit: (data: Partial<NetworkDevice>) => void;
  isSubmitting?: boolean;
  submitLabel?: string;
}

const DEVICE_TYPES: { value: DeviceType; label: string }[] = [
  { value: 'router', label: 'Router' },
  { value: 'switch', label: 'Switch' },
  { value: 'radio', label: 'Radio' },
  { value: 'access_point', label: 'Access Point' },
  { value: 'olt', label: 'OLT' },
  { value: 'onu', label: 'ONU' },
  { value: 'ups', label: 'UPS' },
  { value: 'other', label: 'Other' },
];

const STATUSES: { value: NetworkDevice['status']; label: string }[] = [
  { value: 'inventory', label: 'Inventory' },
  { value: 'deployed', label: 'Deployed' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'offline', label: 'Offline' },
  { value: 'decommissioned', label: 'Decommissioned' },
];

export const DeviceForm = ({
  initialData = {},
  popSites,
  towers,
  mikrotikRouters,
  onSubmit,
  isSubmitting,
  submitLabel = 'Save',
}: DeviceFormProps) => {
  const [formData, setFormData] = useState<Partial<NetworkDevice>>({
    pop_site_id: null,
    tower_id: null,
    name: '',
    device_type: 'router',
    vendor: '',
    model: '',
    serial_number: '',
    mac_address: '',
    ip_address: '',
    firmware_version: '',
    location_description: '',
    management_vlan: null,
    management_username: '',
    management_password: '',
    status: 'inventory',
    notes: '',
    mikrotik_router_id: null,
    ...initialData,
  });

  const handleChange = (field: string, value: string | number | null) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  const filteredTowers = formData.pop_site_id
    ? towers.filter((t) => t.pop_site_id === Number(formData.pop_site_id))
    : towers;

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <Label htmlFor="device_type" className="block text-sm font-medium text-slate-700">
            Device Type *
          </Label>
          <Select value={formData.device_type || 'router'} onValueChange={(v) => handleChange('device_type', v)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select device type" />
            </SelectTrigger>
            <SelectContent>
              {DEVICE_TYPES.map((d) => (
                <SelectItem key={d.value} value={d.value}>
                  {d.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="status" className="block text-sm font-medium text-slate-700">
            Status *
          </Label>
          <Select value={formData.status || 'inventory'} onValueChange={(v) => handleChange('status', v)}>
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
          <Label htmlFor="name" className="block text-sm font-medium text-slate-700">
            Device Name *
          </Label>
          <Input
            id="name"
            value={formData.name || ''}
            onChange={(e) => handleChange('name', e.target.value)}
            placeholder="Enter device name"
            required
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="vendor" className="block text-sm font-medium text-slate-700">
            Vendor
          </Label>
          <Input
            id="vendor"
            value={formData.vendor || ''}
            onChange={(e) => handleChange('vendor', e.target.value)}
            placeholder="MikroTik, Ubiquiti, Cisco, etc."
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="model" className="block text-sm font-medium text-slate-700">
            Model
          </Label>
          <Input
            id="model"
            value={formData.model || ''}
            onChange={(e) => handleChange('model', e.target.value)}
            placeholder="e.g., RB4011, USW-Pro-24"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="serial_number" className="block text-sm font-medium text-slate-700">
            Serial Number
          </Label>
          <Input
            id="serial_number"
            value={formData.serial_number || ''}
            onChange={(e) => handleChange('serial_number', e.target.value)}
            placeholder="Unique serial number"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="mac_address" className="block text-sm font-medium text-slate-700">
            MAC Address
          </Label>
          <Input
            id="mac_address"
            value={formData.mac_address || ''}
            onChange={(e) => handleChange('mac_address', e.target.value.toUpperCase())}
            placeholder="AA:BB:CC:DD:EE:FF"
            className="mt-1"
            maxLength={17}
          />
        </div>

        <div>
          <Label htmlFor="ip_address" className="block text-sm font-medium text-slate-700">
            IP Address
          </Label>
          <Input
            id="ip_address"
            value={formData.ip_address || ''}
            onChange={(e) => handleChange('ip_address', e.target.value)}
            placeholder="192.168.1.1"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="firmware_version" className="block text-sm font-medium text-slate-700">
            Firmware Version
          </Label>
          <Input
            id="firmware_version"
            value={formData.firmware_version || ''}
            onChange={(e) => handleChange('firmware_version', e.target.value)}
            placeholder="e.g., 7.12, 1.10.0"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="pop_site_id" className="block text-sm font-medium text-slate-700">
            POP Site
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
          <Label htmlFor="tower_id" className="block text-sm font-medium text-slate-700">
            Tower
          </Label>
          <Select value={String(formData.tower_id || '')} onValueChange={(v) => handleChange('tower_id', v ? Number(v) : null)}>
            <SelectTrigger className="mt-1" disabled={filteredTowers.length === 0}>
              <SelectValue placeholder={filteredTowers.length === 0 ? 'Select POP site first' : 'Select tower'} />
            </SelectTrigger>
            <SelectContent>
              {filteredTowers.map((tower) => (
                <SelectItem key={tower.id} value={String(tower.id)}>
                  {tower.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="location_description" className="block text-sm font-medium text-slate-700">
            Location Description
          </Label>
          <Input
            id="location_description"
            value={formData.location_description || ''}
            onChange={(e) => handleChange('location_description', e.target.value)}
            placeholder="e.g., Rack 3, Shelf 2, Top shelf"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="management_vlan" className="block text-sm font-medium text-slate-700">
            Management VLAN
          </Label>
          <Input
            id="management_vlan"
            type="number"
            min="1"
            max="4094"
            value={formData.management_vlan ?? ''}
            onChange={(e) => handleChange('management_vlan', e.target.value ? Number(e.target.value) : null)}
            placeholder="1-4094"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="management_username" className="block text-sm font-medium text-slate-700">
            Management Username
          </Label>
          <Input
            id="management_username"
            value={formData.management_username || ''}
            onChange={(e) => handleChange('management_username', e.target.value)}
            placeholder="admin"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="management_password" className="block text-sm font-medium text-slate-700">
            Management Password
          </Label>
          <Input
            id="management_password"
            type="password"
            value={formData.management_password || ''}
            onChange={(e) => handleChange('management_password', e.target.value)}
            placeholder="Leave blank to keep current"
            className="mt-1"
            autoComplete="new-password"
          />
        </div>

        <div>
          <Label htmlFor="mikrotik_router_id" className="block text-sm font-medium text-slate-700">
            Link to MikroTik Router
          </Label>
          <Select value={String(formData.mikrotik_router_id || '')} onValueChange={(v) => handleChange('mikrotik_router_id', v ? Number(v) : null)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select MikroTik router (optional)" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">— None —</SelectItem>
              {mikrotikRouters.map((router) => (
                <SelectItem key={router.id} value={String(router.id)}>
                  {router.name}
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
            placeholder="Additional notes about this device..."
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
