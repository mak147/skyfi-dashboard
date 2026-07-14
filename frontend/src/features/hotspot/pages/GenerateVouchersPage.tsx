import { useNavigate } from 'react-router-dom';

import { VoucherGenerator } from '../components/VoucherGenerator';

export const GenerateVouchersPage = () => {
  const navigate = useNavigate();

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Generate Voucher Batch</h1>
          <p className="mt-1 text-sm text-slate-500">
            Create a batch of unique, printable hotspot access vouchers.
          </p>
        </div>
      </div>

      <VoucherGenerator
        onSuccess={() => navigate('/hotspot/vouchers')}
        onCancel={() => navigate('/hotspot/vouchers')}
      />
    </div>
  );
};
