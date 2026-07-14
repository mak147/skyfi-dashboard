import { useState } from 'react';
import { clsx } from 'clsx';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { PopSite, PowerStatus } from '../types';

interface PopSiteFormProps {
  initialData?: Partial<PopSite>;
  onSubmit: (data: Partial<PopSite>) => void;
  isSubmitting?: boolean;
  submitLabel?: string;
}

const POWER_STATUSES: { value: PowerStatus; label: string }[] = [
  { value: 'unknown', label: 'Unknown' },
  { value: 'grid', label: 'Grid Power' },
  { value: 'solar', label: 'Solar' },
  { value: 'generator', label: 'Generator' },
  { value: 'hybrid', label: 'Hybrid' },
];

const STATUSES: { value: PopSite['status']; label: string }[] = [
  { value: 'planning', label: 'Planning' },
  { value: 'active', label: 'Active' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'decommissioned', label: 'Decommissioned' },
];

export const PopSiteForm = ({
  initialData = {},
  onSubmit,
  isSubmitting,
  submitLabel = 'Save',
}: PopSiteFormProps) => {
  const [formData, setFormData] = useState<Partial<PopSite>>({
    name: '',
    code: '',
    address_line1: '',
    address_line2: '',
    city: '',
    region: '',
    country: 'Pakistan',
    gps_latitude: '',
    gps_longitude: '',
    contact_person: '',
    contact_phone: '',
    contact_email: '',
    power_status: 'unknown',
    fiber_provider: '',
    status: 'planning',
    notes: '',
    ...initialData,
  });

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2">
          <Label htmlFor="name" className="block text-sm font-medium text-slate-700">
            Site Name *
          </Label>
          <Input
            id="name"
            value={formData.name || ''}
            onChange={(e) => handleChange('name', e.target.value)}
            placeholder="Enter site name"
            required
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="code" className="block text-sm font-medium text-slate-700">
            Code *
          </Label>
          <Input
            id="code"
            value={formData.code || ''}
            onChange={(e) => handleChange('code', e.target.value.toUpperCase())}
            placeholder="SITE001"
            required
            className="mt-1"
            maxLength={50}
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
          <Label htmlFor="address_line1" className="block text-sm font-medium text-slate-700">
            Address Line 1
          </Label>
          <Input
            id="address_line1"
            value={formData.address_line1 || ''}
            onChange={(e) => handleChange('address_line1', e.target.value)}
            placeholder="Street address"
            className="mt-1"
          />
        </div>

        <div className="sm:col-span-2">
          <Label htmlFor="address_line2" className="block text-sm font-medium text-slate-700">
            Address Line 2
          </Label>
          <Input
            id="address_line2"
            value={formData.address_line2 || ''}
            onChange={(e) => handleChange('address_line2', e.target.value)}
            placeholder="Suite, floor, etc."
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
          <Label htmlFor="country" className="block text-sm font-medium text-slate-700">
            Country
          </Label>
          <Input
            id="country"
            value={formData.country || 'Pakistan'}
            onChange={(e) => handleChange('country', e.target.value)}
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="power_status" className="block text-sm font-medium text-slate-700">
            Power Status
          </Label>
          <Select value={formData.power_status || 'unknown'} onValueChange={(v) => handleChange('power_status', v)}>
            <SelectTrigger className="mt-1">
              <SelectValue placeholder="Select power status" />
            </SelectTrigger>
            <SelectContent>
              {POWER_STATUSES.map((p) => (
                <SelectItem key={p.value} value={p.value}>
                  {p.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label htmlFor="fiber_provider" className="block text-sm font-medium text-slate-700">
            Fiber Provider
          </Label>
          <Input
            id="fiber_provider"
            value={formData.fiber_provider || ''}
            onChange={(e) => handleChange('fiber_provider', e.target.value)}
            placeholder="ISP or fiber provider name"
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

        <div>
          <Label htmlFor="contact_person" className="block text-sm font-medium text-slate-700">
            Contact Person
          </Label>
          <Input
            id="contact_person"
            value={formData.contact_person || ''}
            onChange={(e) => handleChange('contact_person', e.target.value)}
            placeholder="Contact person name"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="contact_phone" className="block text-sm font-medium text-slate-700">
            Contact Phone
          </Label>
          <Input
            id="contact_phone"
            type="tel"
            value={formData.contact_phone || ''}
            onChange={(e) => handleChange('contact_phone', e.target.value)}
            placeholder="+92 3XX XXXXXXX"
            className="mt-1"
          />
        </div>

        <div>
          <Label htmlFor="contact_email" className="block text-sm font-medium text-slate-700">
            Contact Email
          </Label>
          <Input
            id="contact_email"
            type="email"
            value={formData.contact_email || ''}
            onChange={(e) => handleChange('contact_email', e.target.value)}
            placeholder="contact@example.com"
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
            placeholder="Additional notes about this POP site..."
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
