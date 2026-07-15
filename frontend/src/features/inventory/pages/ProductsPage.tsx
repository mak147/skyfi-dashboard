import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router-dom';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/usePermissions';
import { apiErrorMessage } from '@/lib/apiClient';
import { inventoryApi } from '../api/inventoryApi';
import { InventoryModal } from '../components/InventoryModal';
import { ProductForm } from '../components/ProductForm';
import { ProductTable } from '../components/ProductTable';
import type { ProductSchemaValues } from '../schemas';
import type { Product, ProductFormValues } from '../types';

export const ProductsPage = () => {
  const [params, setParams] = useSearchParams(); const [editing, setEditing] = useState<Product | null | undefined>(); const client = useQueryClient(); const { can } = usePermissions();
  const page = Math.max(1, Number(params.get('page') || 1)); const filters = { page, per_page: 20, search: params.get('search') || undefined, status: params.get('status') || undefined, tracking_mode: params.get('tracking_mode') || undefined, category_id: params.get('category_id') || undefined, low_stock: params.get('low_stock') || undefined, sort: params.get('sort') || '-created_at' };
  const products = useQuery({ queryKey: ['inventory','products',filters], queryFn:()=>inventoryApi.products(filters) });
  const categories = useQuery({queryKey:['inventory','categories'],queryFn:()=>inventoryApi.catalog('categories')}); const models=useQuery({queryKey:['inventory','models'],queryFn:()=>inventoryApi.catalog('models')}); const units=useQuery({queryKey:['inventory','units'],queryFn:()=>inventoryApi.catalog('units')});
  const save=useMutation({mutationFn:(values:ProductSchemaValues)=>editing?inventoryApi.updateProduct(editing.id,values as ProductFormValues):inventoryApi.createProduct(values as ProductFormValues),onSuccess:()=>{setEditing(undefined);void client.invalidateQueries({queryKey:['inventory']});}});
  const remove=useMutation({mutationFn:inventoryApi.deleteProduct,onSuccess:()=>void client.invalidateQueries({queryKey:['inventory']})});
  const set=(key:string,value:string)=>{const next=new URLSearchParams(params);if(value)next.set(key,value);else next.delete(key);if(key!=='page')next.delete('page');setParams(next,{replace:true});};
  const items=products.data?.data.map((resource)=>resource.attributes)??[];
  return <div className="space-y-5"><header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h1 className="text-2xl font-bold text-slate-900">Products</h1><p className="mt-1 text-sm text-slate-500">Manage SKU definitions, tracking methods, reorder levels, and valuation defaults.</p></div>{can('inventory.create')&&<Button onClick={()=>setEditing(null)}>Create product</Button>}</header>
    <div className="flex flex-wrap gap-3 rounded-xl border bg-white p-4"><input className="h-10 min-w-56 rounded-md border px-3 text-sm" placeholder="Search SKU, barcode, or product" value={params.get('search')||''} onChange={(event)=>set('search',event.target.value)}/><select className="h-10 rounded-md border bg-white px-3 text-sm" value={params.get('tracking_mode')||''} onChange={(event)=>set('tracking_mode',event.target.value)}><option value="">All tracking</option><option value="quantity">Quantity</option><option value="serialized">Serialized</option></select><select className="h-10 rounded-md border bg-white px-3 text-sm" value={params.get('category_id')||''} onChange={(event)=>set('category_id',event.target.value)}><option value="">All categories</option>{categories.data?.map((category)=><option key={category.id} value={category.id}>{category.name}</option>)}</select><label className="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" checked={params.get('low_stock')==='true'} onChange={(event)=>set('low_stock',event.target.checked?'true':'')}/>Low stock only</label></div>
    {(products.error||save.error||remove.error)&&<Alert title="Inventory operation failed">{apiErrorMessage(products.error||save.error||remove.error)}</Alert>}<ProductTable products={items} isLoading={products.isLoading} onEdit={can('inventory.update')?setEditing:undefined} onDelete={can('inventory.delete')?(product)=>{if(confirm(`Delete ${product.name}?`))remove.mutate(product.id);}:undefined}/>
    {products.data&&<div className="flex items-center justify-between rounded-xl border bg-white p-3 text-sm"><span className="text-slate-500">{products.data.meta.total} products</span><div className="flex gap-2"><Button size="sm" variant="secondary" disabled={page<=1} onClick={()=>set('page',String(page-1))}>Previous</Button><Button size="sm" variant="secondary" disabled={page>=products.data.meta.last_page} onClick={()=>set('page',String(page+1))}>Next</Button></div></div>}
    {editing!==undefined&&<InventoryModal title={editing?'Edit product':'Create product'} onClose={()=>setEditing(undefined)}><ProductForm product={editing} categories={categories.data??[]} models={models.data??[]} units={units.data??[]} onSubmit={(values)=>save.mutate(values)} onCancel={()=>setEditing(undefined)} isLoading={save.isPending}/></InventoryModal>}
  </div>;
};
