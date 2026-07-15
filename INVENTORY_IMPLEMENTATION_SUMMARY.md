# Inventory & Asset Management Module

## Delivered scope

- Normalized catalog for categories, brands, models, units, vendors, products, and product-vendor sourcing.
- Multiple warehouses with nested storage locations and technician-vehicle warehouse support.
- Quantity stock balances with condition and weighted-average valuation.
- Serialized asset registry with serial, MAC, IMEI placeholder, warranty, vendor, infrastructure link, current assignment, and immutable timeline.
- Stock in/out, opening balances, positive/negative adjustments, returns, damaged stock, scrap, and reversal operations.
- Draft-to-receipt warehouse transfers with approval, dispatch, partial receipt, serialized assets, and transfer movements.
- Customer, tower, POP-site, technician, network-device, support-ticket, Finance, and future Purchasing integration points.
- Idempotent Finance posting queue with configurable Chart of Accounts mappings.
- RBAC permissions: `inventory.view`, `inventory.create`, `inventory.update`, `inventory.delete`, `inventory.transfer`, `inventory.audit`, and `inventory.manage`.
- React pages for dashboard, products, assets, asset timeline, warehouses, movements, transfers, and catalog settings.
- Search, filtering, sorting, pagination, responsive tables/cards, loading/error/empty states, barcode placeholder, and QR placeholder.

## Main API prefix

`/api/v1/inventory`

## Migration

`backend/database/migrations/2026_07_30_000000_create_inventory_tables.sql`

Apply after all migrations through `2026_07_29_000000_create_support_tables.sql`, then run the existing RBAC seeder so the Inventory Manager role receives the new permission set.

## Accounting behavior

Inventory movements are committed independently and create a pending `inventory_finance_postings` record. If all inventory accounting mappings are configured, the posting is converted into a balanced journal entry through the existing Finance service. Failed postings remain visible and safely retryable without duplicating journals.

Warehouse transfers are value-neutral and marked as not requiring a Finance journal entry.
