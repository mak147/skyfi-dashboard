# Vendor & Supplier Management Module — Implementation Summary

## Delivered Scope

- Canonical supplier CRUD built on the existing `vendors` table, preserving Inventory and Purchasing foreign keys.
- Supplier activation, inactive/on-hold status, archive/restore, categories, contacts, contracts, quotations, ratings, and performance.
- Multiple supplier contacts with transactional primary and emergency designations.
- Contract values, dates, renewal tracking, expiry indicators, and attachment-reference placeholders.
- Supplier quotations with normalized line items, RFQ reference placeholders, history, and same-currency price comparison.
- Operational supplier performance derived from purchase orders and goods receipts: delivery, completion, returns, lead time, procurement value, and weighted overall rating snapshots.
- Supplier dashboard with active supplier, expiring contract, top supplier, spend-by-supplier, and average-rating widgets.
- Purchasing, Inventory, and Finance read-model integrations without duplicating source records.
- Search, filtering, allowlisted sorting, pagination, loading skeletons, empty states, error states, responsive layouts, and class-based dark mode.

## Backend

`backend/src/Vendors/` contains Contracts, Controllers, DTOs, DomainModels, Repositories, Routes, Services, and Validators.

API prefix: `/api/v1/vendors`

The module exposes supplier CRUD/status actions, category management, nested contact/contract/quotation/rating resources, quotation comparison/history, performance, dashboard, purchase-order history, product sourcing history, and financial references.

## Database

Migration: `backend/database/migrations/2026_08_01_000000_create_vendor_management_tables.sql`

- Extends `vendors` with registration, address, country, and currency fields.
- Adds `supplier_categories`, `supplier_category_assignments`, `supplier_contacts`, `supplier_contracts`, `supplier_quotations`, `supplier_quotation_items`, and `supplier_ratings`.
- Backfills existing vendor contact information as primary supplier contacts.
- Includes foreign keys, audit users/timestamps, soft deletes, checks, and query indexes.

## Frontend

`frontend/src/features/vendors/` includes:

- Supplier Dashboard
- Supplier List and Supplier Details
- Contacts
- Contracts
- Quotations and Price Comparison
- Required SupplierTable, SupplierForm, ContactTable, ContractTimeline, PerformanceCards, and SupplierStatistics components
- Additional forms, status badges, skeletons, category manager, rating review, and comparison components

Routes are mounted under `/vendors` and navigation is permission-aware.

## RBAC

- `vendors.view`
- `vendors.create`
- `vendors.update`
- `vendors.delete`
- `vendors.contracts`
- `vendors.manage`

Inventory Manager receives all Vendor permissions; Company Owner and Finance Department receive read access. The legacy `manage:vendor` permission remains for compatibility.

## Integration Notes

- Purchase orders and supplier invoices continue to reference `vendors.id`.
- Purchasing supplier ID inputs now use active supplier selections.
- Inventory product-vendor, asset, and movement relationships remain unchanged.
- Supplier financial references expose existing supplier invoices and purchasing finance postings.
- Monetary summaries and quotation comparisons do not convert currencies.

## Scope Exclusions

No Field Service, Reports, Customer Installation, Queue Management, Accounts Payable, RFQ workflow, or attachment upload/storage was implemented.
