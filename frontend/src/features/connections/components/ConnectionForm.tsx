import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Button } from '@/components/ui/button';
import { getCustomers } from '@/features/customers/api/customerApi';
import { getPackages } from '@/features/packages/api/packageApi';
import type { ConnectionFormData, Connection } from '../types';

interface ConnectionFormProps {
  initialData?: Partial<Connection>;
  onSubmit: (data: ConnectionFormData) => void;
  isLoading?: boolean;
}

export const ConnectionForm: React.FC<ConnectionFormProps> = ({ initialData, onSubmit, isLoading }) => {
  const [formData, setFormData] = React.useState<ConnectionFormData>({
    name: initialData?.name || '',
    customer_id: initialData?.customer_id || 0,
    package_id: initialData?.package_id || 0,
    type: initialData?.type || 'pppoe',
    pppoe_username: initialData?.pppoe_username || '',
    pppoe_password: initialData?.pppoe_password || '',
    static_ip: initialData?.static_ip || '',
    gateway: initialData?.gateway || '',
    dns_servers: initialData?.dns_servers || '',
    mac_address: initialData?.mac_address || '',
    installation_cost: initialData?.installation_cost || 0,
    installation_notes: initialData?.installation_notes || '',
  });

  const customersQuery = useQuery({
    queryKey: ['customers', 'lookup'],
    queryFn: () => getCustomers(1, 100, {}, 'full_name'),
  });

  const packagesQuery = useQuery({
    queryKey: ['packages', 'lookup'],
    queryFn: () => getPackages(1, 100, {}, 'name'),
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'number' ? Number(value) : value,
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-8">
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
        <section className="space-y-4">
          <h3 className="text-lg font-medium text-slate-900">General Information</h3>
          <div className="space-y-2">
            <label className="text-sm font-medium text-slate-700">Connection Name</label>
            <input
              type="text"
              name="name"
              required
              className="w-full rounded-lg border-slate-200 text-sm"
              value={formData.name}
              onChange={handleChange}
            />
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium text-slate-700">Customer</label>
            <select
              name="customer_id"
              required
              disabled={!!initialData?.id}
              className="w-full rounded-lg border-slate-200 text-sm"
              value={formData.customer_id}
              onChange={handleChange}
            >
              <option value="">Select Customer</option>
              {customersQuery.data?.data.map((c) => (
                <option key={c.id} value={Number(c.id)}>{c.attributes.full_name} ({c.attributes.customer_code})</option>
              ))}
            </select>
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium text-slate-700">Internet Package</label>
            <select
              name="package_id"
              required
              className="w-full rounded-lg border-slate-200 text-sm"
              value={formData.package_id}
              onChange={handleChange}
            >
              <option value="">Select Package</option>
              {packagesQuery.data?.data.map((p) => (
                <option key={p.id} value={Number(p.id)}>{p.attributes.name}</option>
              ))}
            </select>
          </div>
          <div className="space-y-2">
            <label className="text-sm font-medium text-slate-700">Connection Type</label>
            <select
              name="type"
              required
              className="w-full rounded-lg border-slate-200 text-sm"
              value={formData.type}
              onChange={handleChange}
            >
              <option value="pppoe">PPPoE</option>
              <option value="hotspot">Hotspot</option>
              <option value="static_ip">Static IP</option>
            </select>
          </div>
        </section>

        <section className="space-y-4">
          <h3 className="text-lg font-medium text-slate-900">Network Configuration</h3>
          {formData.type === 'pppoe' && (
            <>
              <div className="space-y-2">
                <label className="text-sm font-medium text-slate-700">PPPoE Username</label>
                <input
                  type="text"
                  name="pppoe_username"
                  className="w-full rounded-lg border-slate-200 text-sm"
                  value={formData.pppoe_username}
                  onChange={handleChange}
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium text-slate-700">PPPoE Password</label>
                <input
                  type="password"
                  name="pppoe_password"
                  className="w-full rounded-lg border-slate-200 text-sm"
                  value={formData.pppoe_password}
                  onChange={handleChange}
                />
              </div>
            </>
          )}
          {formData.type === 'static_ip' && (
            <div className="space-y-2">
              <label className="text-sm font-medium text-slate-700">Static IP</label>
              <input
                type="text"
                name="static_ip"
                className="w-full rounded-lg border-slate-200 text-sm"
                value={formData.static_ip}
                onChange={handleChange}
              />
            </div>
          )}
          <div className="space-y-2">
            <label className="text-sm font-medium text-slate-700">MAC Address</label>
            <input
              type="text"
              name="mac_address"
              className="w-full rounded-lg border-slate-200 text-sm"
              value={formData.mac_address}
              onChange={handleChange}
            />
          </div>
        </section>
      </div>

      <div className="flex justify-end gap-3">
        <Button type="button" variant="secondary" onClick={() => window.history.back()}>
          Cancel
        </Button>
        <Button type="submit" isLoading={isLoading}>
          {initialData?.id ? 'Update Connection' : 'Create Connection'}
        </Button>
      </div>
    </form>
  );
};
