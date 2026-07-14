import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams, useNavigate } from 'react-router-dom';

import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';

import { getRouterGroups, getRouters, getRouterTags, setRouterEnabled } from '../api/mikrotikApi';
import { RouterCard } from '../components/RouterCard';
import { RouterTable } from '../components/RouterTable';
import type { MikrotikRouter, RouterListFilters, RouterStatus } from '../types';

export const RouterListPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { can } = usePermissions();
  const page = Math.max(1, Number(searchParams.get('page') ?? '1'));
  const sort = searchParams.get('sort') ?? '-created_at';
  const filters: RouterListFilters = {
    search: searchParams.get('search') || undefined, router_group_id: searchParams.get('router_group_id') || undefined,
    tag_id: searchParams.get('tag_id') || undefined, site: searchParams.get('site') || undefined,
    status: (searchParams.get('status') as RouterStatus | '') || undefined, is_enabled: (searchParams.get('is_enabled') as '' | 'true' | 'false') || undefined,
  };
  const routers = useQuery({ queryKey: ['mikrotik', 'routers', page, filters, sort], queryFn: () => getRouters(page, 15, filters, sort) });
  const groups = useQuery({ queryKey: ['mikrotik', 'router-groups'], queryFn: getRouterGroups });
  const tags = useQuery({ queryKey: ['mikrotik', 'router-tags'], queryFn: getRouterTags });
  const toggle = useMutation({ mutationFn: ({ id, enabled }: { id: number; enabled: boolean }) => setRouterEnabled(id, enabled), onSuccess: () => queryClient.invalidateQueries({ queryKey: ['mikrotik', 'routers'] }) });

  const updateParams = (next: RouterListFilters, nextPage = 1) => {
    const params = new URLSearchParams();
    if (nextPage > 1) params.set('page', String(nextPage));
    if (sort !== '-created_at') params.set('sort', sort);
    Object.entries(next).forEach(([key, value]) => { if (value) params.set(key, value); });
    setSearchParams(params, { replace: true });
  };
  const routerItems = routers.data?.data.map((item) => item.attributes) ?? [];
  const meta = routers.data?.meta;

  return <div className="space-y-6">
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 className="text-2xl font-bold tracking-tight text-slate-900">MikroTik Routers</h1><p className="mt-1 text-sm text-slate-500">Manage secured RouterOS API connections, discovery, and router health.</p></div>{can('mikrotik.create') ? <Button onClick={() => navigate('/network/routers/new')}>Add router</Button> : null}</div>
    <section className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3 xl:grid-cols-6"><input className="h-10 rounded-md border border-slate-300 px-3 text-sm md:col-span-2" placeholder="Search name, host, site…" value={filters.search ?? ''} onChange={(event) => updateParams({ ...filters, search: event.target.value || undefined })} /><select className="h-10 rounded-md border border-slate-300 px-3 text-sm" value={filters.router_group_id ?? ''} onChange={(event) => updateParams({ ...filters, router_group_id: event.target.value || undefined })}><option value="">All groups</option>{groups.data?.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}</select><select className="h-10 rounded-md border border-slate-300 px-3 text-sm" value={filters.tag_id ?? ''} onChange={(event) => updateParams({ ...filters, tag_id: event.target.value || undefined })}><option value="">All tags</option>{tags.data?.map((tag) => <option key={tag.id} value={tag.id}>{tag.name}</option>)}</select><select className="h-10 rounded-md border border-slate-300 px-3 text-sm" value={filters.status ?? ''} onChange={(event) => updateParams({ ...filters, status: event.target.value as RouterStatus || undefined })}><option value="">All statuses</option><option value="online">Online</option><option value="offline">Offline</option><option value="unknown">Not checked</option><option value="disabled">Disabled</option></select><select className="h-10 rounded-md border border-slate-300 px-3 text-sm" value={filters.is_enabled ?? ''} onChange={(event) => updateParams({ ...filters, is_enabled: event.target.value as '' | 'true' | 'false' || undefined })}><option value="">Enabled and disabled</option><option value="true">Enabled</option><option value="false">Disabled</option></select></section>
    {routers.error ? <Alert title="Unable to load routers" variant="danger">{apiErrorMessage(routers.error)}</Alert> : null}
    {toggle.error ? <Alert title="Unable to update router" variant="danger">{apiErrorMessage(toggle.error)}</Alert> : null}
    <RouterTable routers={routerItems} isLoading={routers.isLoading} canUpdate={can('mikrotik.update')} onToggleEnabled={(router: MikrotikRouter) => toggle.mutate({ id: router.id, enabled: !router.is_enabled })} />
    <div className="grid gap-4 md:hidden">{routers.isLoading ? Array.from({ length: 3 }).map((_, index) => <div key={index} className="h-32 animate-pulse rounded-xl bg-slate-100" />) : routerItems.map((router) => <RouterCard key={router.id} router={router} />)}</div>
    {meta && meta.last_page > 1 ? <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4"><p className="text-sm text-slate-500">Page {meta.current_page} of {meta.last_page} · {meta.total} routers</p><div className="flex gap-2"><Button size="sm" variant="secondary" disabled={meta.current_page === 1} onClick={() => updateParams(filters, meta.current_page - 1)}>Previous</Button><Button size="sm" variant="secondary" disabled={meta.current_page === meta.last_page} onClick={() => updateParams(filters, meta.current_page + 1)}>Next</Button></div></div> : null}
  </div>;
};
