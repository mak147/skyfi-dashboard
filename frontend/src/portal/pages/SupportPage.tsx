import { Link } from 'react-router-dom';

import { Button } from '@/components/ui/button';

import { TicketList } from '../components/TicketList';

export const SupportPage = () => (
  <div className="space-y-6">
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Support</h1>
        <p className="mt-1 text-sm text-slate-500">View and manage your support tickets.</p>
      </div>
      <Button asChild>
        <Link to="/portal/support/new">Create ticket</Link>
      </Button>
    </div>
    <TicketList />
  </div>
);
