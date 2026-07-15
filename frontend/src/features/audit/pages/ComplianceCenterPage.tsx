import { useState } from 'react';
import { Link } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { useCompliancePolicies, useCreatePolicy, useDeletePolicy, useRetentionPolicies, useCreateRetentionPolicy, useDeleteRetentionPolicy } from '../api/useAudit';
import { ComplianceCards } from '../components/ComplianceCards';
import { AuditSkeleton } from '../components/AuditSkeleton';
import type { CompliancePolicy } from '../types';

export const ComplianceCenterPage = () => {
  const { can } = usePermissions();
  const policies = useCompliancePolicies();
  const retention = useRetentionPolicies();
  const createPolicy = useCreatePolicy();
  const deletePolicy = useDeletePolicy();
  const createRetention = useCreateRetentionPolicy();
  const deleteRetention = useDeleteRetentionPolicy();

  const [showPolicyForm, setShowPolicyForm] = useState(false);
  const [showRetentionForm, setShowRetentionForm] = useState(false);
  const [policyName, setPolicyName] = useState('');
  const [policyType, setPolicyType] = useState<string>('custom');
  const [policyDesc, setPolicyDesc] = useState('');
  const [retName, setRetName] = useState('');
  const [retDays, setRetDays] = useState(365);
  const [retModule, setRetModule] = useState('*');
  const [retDesc, setRetDesc] = useState('');

  if (policies.isLoading || retention.isLoading) {
    return <AuditSkeleton />;
  }

  if (policies.error) {
    return <Alert title="Compliance unavailable">{apiErrorMessage(policies.error)}</Alert>;
  }

  const policyItems = policies.data?.data.map((r) => r.attributes) ?? [];
  const retentionItems = retention.data?.data.map((r) => r.attributes) ?? [];

  return (
    <div className="space-y-8 text-slate-800 dark:text-slate-100">
      <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Audit & Compliance</p>
          <h1 className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Compliance Center</h1>
          <p className="mt-1 text-sm text-slate-500">
            Manage compliance policies and audit log retention rules.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to="/audit">
            <Button variant="secondary">Dashboard</Button>
          </Link>
          <Link to="/audit/retention">
            <Button variant="secondary">Retention Policies</Button>
          </Link>
        </div>
      </header>

      {/* Compliance Policies */}
      <section>
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-bold text-slate-900 dark:text-white">Compliance Policies</h2>
          {can('compliance.manage') && (
            <Button size="sm" onClick={() => setShowPolicyForm(!showPolicyForm)}>
              {showPolicyForm ? 'Cancel' : 'Add Policy'}
            </Button>
          )}
        </div>

        {showPolicyForm && (
          <div className="mb-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
            <h3 className="text-sm font-bold text-slate-900 dark:text-white">New Compliance Policy</h3>
            <div className="mt-3 grid gap-3 sm:grid-cols-2">
              <input
                type="text"
                placeholder="Policy name"
                value={policyName}
                onChange={(e) => setPolicyName(e.target.value)}
                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              <select
                value={policyType}
                onChange={(e) => setPolicyType(e.target.value)}
                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              >
                <option value="data_retention">Data Retention</option>
                <option value="access_control">Access Control</option>
                <option value="immutability">Immutability</option>
                <option value="privacy">Privacy</option>
                <option value="custom">Custom</option>
              </select>
              <textarea
                placeholder="Description"
                value={policyDesc}
                onChange={(e) => setPolicyDesc(e.target.value)}
                className="col-span-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                rows={2}
              />
            </div>
            <div className="mt-3">
              <Button
                size="sm"
                disabled={!policyName.trim()}
                onClick={() => {
                  createPolicy.mutate(
                    { name: policyName, policy_type: policyType as CompliancePolicy['policy_type'], description: policyDesc || undefined, rules: {} },
                    {
                      onSuccess: () => {
                        setPolicyName('');
                        setPolicyDesc('');
                        setShowPolicyForm(false);
                      },
                    },
                  );
                }}
              >
                Create Policy
              </Button>
            </div>
          </div>
        )}

        <ComplianceCards
          policies={policyItems}
          onDelete={can('compliance.manage') ? (id: number) => deletePolicy.mutate(id) : undefined}
        />
      </section>

      {/* Quick Retention Summary */}
      <section>
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-bold text-slate-900 dark:text-white">Retention Policies</h2>
          {can('compliance.manage') && (
            <Button size="sm" onClick={() => setShowRetentionForm(!showRetentionForm)}>
              {showRetentionForm ? 'Cancel' : 'Add Retention'}
            </Button>
          )}
        </div>

        {showRetentionForm && (
          <div className="mb-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
            <h3 className="text-sm font-bold text-slate-900 dark:text-white">New Retention Policy</h3>
            <div className="mt-3 grid gap-3 sm:grid-cols-2">
              <input
                type="text"
                placeholder="Policy name"
                value={retName}
                onChange={(e) => setRetName(e.target.value)}
                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              <input
                type="number"
                placeholder="Retention days"
                value={retDays}
                onChange={(e) => setRetDays(parseInt(e.target.value, 10) || 365)}
                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              <input
                type="text"
                placeholder="Module (e.g. billing or *)"
                value={retModule}
                onChange={(e) => setRetModule(e.target.value)}
                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
              <input
                type="text"
                placeholder="Description"
                value={retDesc}
                onChange={(e) => setRetDesc(e.target.value)}
                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </div>
            <div className="mt-3">
              <Button
                size="sm"
                disabled={!retName.trim()}
                onClick={() => {
                  createRetention.mutate(
                    { name: retName, retention_days: retDays, module: retModule || '*', description: retDesc || undefined },
                    {
                      onSuccess: () => {
                        setRetName('');
                        setRetDays(365);
                        setRetModule('*');
                        setRetDesc('');
                        setShowRetentionForm(false);
                      },
                    },
                  );
                }}
              >
                Create Retention Policy
              </Button>
            </div>
          </div>
        )}

        {retentionItems.length > 0 ? (
          <div className="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-400">
                  <th className="px-4 py-3 text-left">Name</th>
                  <th className="px-4 py-3 text-left">Module</th>
                  <th className="px-4 py-3 text-left">Retention</th>
                  <th className="px-4 py-3 text-left">Auto Archive</th>
                  <th className="px-4 py-3 text-left">Status</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {retentionItems.map((policy) => (
                  <tr key={policy.id} className="border-b border-slate-50 dark:border-slate-800">
                    <td className="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">{policy.name}</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                      <code className="rounded bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-800">{policy.module}</code>
                    </td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{policy.retention_days} days</td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">{policy.auto_archive ? 'Yes' : 'No'}</td>
                    <td className="px-4 py-3">
                      <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${policy.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'}`}>
                        {policy.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right">
                      {can('compliance.manage') && policy.is_active && (
                        <button
                          type="button"
                          onClick={() => deleteRetention.mutate(policy.id)}
                          className="text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400"
                        >
                          Deactivate
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">
            No retention policies configured.
          </div>
        )}
      </section>
    </div>
  );
};
