import React, { useEffect, useState } from 'react';
import { ShieldCheck, FileText, Activity, Clock } from 'lucide-react';
import { backupApi } from '../api/backupApi';
import type { DrPlan } from '../types';

export const DisasterRecoveryPage = () => {
  const [plans, setPlans] = useState<DrPlan[]>([]);
  const [, setLoading] = useState(true);

  useEffect(() => {
    backupApi.getDrPlans().then((res: { data: DrPlan[] }) => {
      setPlans(res.data);
      setLoading(false);
    });
  }, []);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Disaster Recovery</h1>
          <p className="text-slate-500">Business continuity planning and recovery runbooks.</p>
        </div>
        <button className="flex items-center space-x-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">
          <ShieldCheck className="h-4 w-4" />
          <span>DR Readiness Test</span>
        </button>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-1 space-y-6">
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-bold text-slate-900 mb-4">DR Metrics</h2>
            <div className="space-y-4">
              <div className="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                <div className="flex items-center space-x-3">
                  <Activity className="h-5 w-5 text-indigo-600" />
                  <span className="text-sm font-medium text-slate-700">RPO</span>
                </div>
                <span className="text-lg font-bold text-slate-900">60 Min</span>
              </div>
              <div className="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                <div className="flex items-center space-x-3">
                  <Clock className="h-5 w-5 text-indigo-600" />
                  <span className="text-sm font-medium text-slate-700">RTO</span>
                </div>
                <span className="text-lg font-bold text-slate-900">4 Hours</span>
              </div>
            </div>
            <div className="mt-6 p-4 rounded-lg bg-green-50 border border-green-100">
               <div className="flex items-center space-x-2 text-green-700">
                 <ShieldCheck className="h-5 w-5" />
                 <span className="text-sm font-bold">System Status: Resilient</span>
               </div>
               <p className="mt-1 text-xs text-green-600">All primary systems are backed up according to SLA.</p>
            </div>
          </div>
        </div>

        <div className="lg:col-span-2 space-y-6">
          {plans.map((plan) => (
            <div key={plan.id} className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
              <div className="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
                <div className="flex items-center space-x-3">
                  <FileText className="h-5 w-5 text-slate-500" />
                  <h3 className="text-lg font-bold text-slate-900">{plan.name}</h3>
                </div>
                <span className="text-xs text-slate-500">Last updated: {new Date(plan.updated_at).toLocaleDateString()}</span>
              </div>
              <div className="p-6">
                <p className="text-slate-600 mb-6">{plan.description}</p>
                <div className="prose prose-sm max-w-none prose-slate">
                  <pre className="p-4 bg-slate-900 text-slate-100 rounded-lg whitespace-pre-wrap font-sans text-sm leading-relaxed">
                    {plan.content}
                  </pre>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
