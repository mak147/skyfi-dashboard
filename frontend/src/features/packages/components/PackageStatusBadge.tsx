import { clsx } from 'clsx';import type { PackageStatus } from '../types';
const styles:Record<PackageStatus,string>={active:'bg-emerald-50 text-emerald-700 ring-emerald-200',draft:'bg-amber-50 text-amber-700 ring-amber-200',inactive:'bg-slate-100 text-slate-600 ring-slate-200',archived:'bg-red-50 text-red-700 ring-red-200'};
export const PackageStatusBadge=({status}:{status:PackageStatus})=><span className={clsx('inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1',styles[status])}>{status}</span>;
