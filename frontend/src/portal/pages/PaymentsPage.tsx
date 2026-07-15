import { PaymentTable } from '../components/PaymentTable';

export const PaymentsPage = () => (
  <div className="space-y-6">
    <div>
      <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Payments</h1>
      <p className="mt-1 text-sm text-slate-500">View your payment history and receipts.</p>
    </div>
    <PaymentTable />
  </div>
);
