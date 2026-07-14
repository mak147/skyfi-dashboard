import React from 'react';
import { clsx } from 'clsx';
import type { ConnectionStatus } from '../types';

interface InstallationTimelineProps {
  status: ConnectionStatus;
  createdAt: string;
}

const steps: { status: ConnectionStatus; label: string; description: string }[] = [
  { status: 'pending', label: 'Requested', description: 'Service request received' },
  { status: 'scheduled', label: 'Scheduled', description: 'Installation date set' },
  { status: 'installing', label: 'Installing', description: 'Technician on-site' },
  { status: 'active', label: 'Active', description: 'Service activated' },
];

export const InstallationTimeline: React.FC<InstallationTimelineProps> = ({ status, createdAt }) => {
  const currentStepIndex = steps.findIndex((s) => s.status === status);
  const isAfterActive = ['suspended', 'disconnected', 'archived'].includes(status);
  const effectiveIndex = isAfterActive ? 3 : currentStepIndex;

  return (
    <div className="flow-root">
      <ul role="list" className="-mb-8">
        {steps.map((step, idx) => (
          <li key={step.status}>
            <div className="relative pb-8">
              {idx !== steps.length - 1 ? (
                <span
                  className={clsx(
                    'absolute left-4 top-4 -ml-px h-full w-0.5',
                    idx < effectiveIndex ? 'bg-indigo-500' : 'bg-slate-200'
                  )}
                  aria-hidden="true"
                />
              ) : null}
              <div className="relative flex space-x-3">
                <div>
                  <span
                    className={clsx(
                      'flex h-8 w-8 items-center justify-center rounded-full ring-8 ring-white',
                      idx <= effectiveIndex ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-400'
                    )}
                  >
                    {idx < effectiveIndex ? '✓' : idx + 1}
                  </span>
                </div>
                <div className="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                  <div>
                    <p className="text-sm text-slate-900 font-medium">{step.label}</p>
                    <p className="mt-0.5 text-xs text-slate-500">{step.description}</p>
                  </div>
                  {idx === 0 && (
                    <div className="whitespace-nowrap text-right text-xs text-slate-400">
                      {new Date(createdAt).toLocaleDateString()}
                    </div>
                  )}
                </div>
              </div>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
};
