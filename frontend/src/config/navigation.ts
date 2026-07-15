import type { AuthUser } from '@/features/authentication/types';

const ADMIN_ROLES = ['Super Administrator'];

export interface NavigationItem {
  label: string;
  path: string;
  icon: string;
  description: string;
  allowedRoles?: string[];
  requiredPermission?: string;
}

export interface NavigationGroup {
  label: string;
  items: NavigationItem[];
}

export const navigationGroups: NavigationGroup[] = [
  {
    label: 'Workspace',
    items: [
      {
        label: 'Dashboard',
        path: '/dashboard',
        icon: '⌂',
        description: 'Role-aware operational KPIs and activity widgets.',
      },
      {
        label: 'Customers',
        path: '/customers',
        icon: '👤',
        description: 'Manage customer lifecycle from leads to archived accounts.',
      },
      {
        label: 'Connections',
        path: '/connections',
        icon: '⚡',
        description: 'Manage internet service connections and network profiles.',
        requiredPermission: 'connections.view',
      },
      {
        label: 'Internet Packages',
        path: '/packages',
        icon: '◫',
        description: 'Manage pricing, bandwidth, and service package profiles.',
        requiredPermission: 'packages.view',
      },
      {
        label: 'Billing',
        path: '/billing',
        icon: '🧾',
        description: 'Generate and manage customer invoices and billing schedules.',
        requiredPermission: 'billing.view',
      },
      {
        label: 'Payments',
        path: '/payments',
        icon: '💳',
        description: 'Receive, allocate, reconcile, and refund customer payments.',
        requiredPermission: 'payments.view',
      },
      {
        label: 'Finance & Accounting',
        path: '/finance',
        icon: '📊',
        description: 'Manage chart of accounts, general ledger, and journal entries.',
        requiredPermission: 'finance.view',
      },
    ],
  },
  {
    label: 'Inventory',
    items: [
      {
        label: 'Inventory Dashboard',
        path: '/inventory',
        icon: '▦',
        description: 'Stock health, serialized assets, valuation, and transfer workload.',
        requiredPermission: 'inventory.view',
      },
      {
        label: 'Products',
        path: '/inventory/products',
        icon: '◫',
        description: 'SKU catalog, categories, models, units, and reorder levels.',
        requiredPermission: 'inventory.view',
      },
      {
        label: 'Assets',
        path: '/inventory/assets',
        icon: '◇',
        description: 'Serialized equipment, warranty, custody, and deployment history.',
        requiredPermission: 'inventory.view',
      },
      {
        label: 'Warehouses',
        path: '/inventory/warehouses',
        icon: '⌂',
        description: 'Storage sites, technician vehicles, bins, and stock locations.',
        requiredPermission: 'inventory.view',
      },
      {
        label: 'Stock Movements',
        path: '/inventory/stock-movements',
        icon: '⇅',
        description: 'Receipts, issues, adjustments, returns, damage, and scrap.',
        requiredPermission: 'inventory.view',
      },
      {
        label: 'Transfers',
        path: '/inventory/transfers',
        icon: '⇄',
        description: 'Warehouse transfer approvals, dispatch, and receipt.',
        requiredPermission: 'inventory.view',
      },
      {
        label: 'Catalog Settings',
        path: '/inventory/categories',
        icon: '⚙',
        description: 'Categories, brands, models, units, and vendors.',
        requiredPermission: 'inventory.view',
      },
    ],
  },
  {
    label: 'Purchasing',
    items: [
      {
        label: 'Procurement Dashboard',
        path: '/purchasing',
        icon: '◈',
        description: 'Purchase lifecycle, approvals, deliveries, and spend.',
        requiredPermission: 'purchasing.view',
      },
      {
        label: 'Purchase Requests',
        path: '/purchasing/requests',
        icon: '◫',
        description: 'Internal procurement requests and approval workflow.',
        requiredPermission: 'purchasing.view',
      },
      {
        label: 'Purchase Orders',
        path: '/purchasing/orders',
        icon: '⊞',
        description: 'Supplier purchase orders, delivery, and closure.',
        requiredPermission: 'purchasing.view',
      },
      {
        label: 'Goods Receipts',
        path: '/purchasing/receipts',
        icon: '⇥',
        description: 'Record received goods, damages, and short receipts.',
        requiredPermission: 'purchasing.view',
      },
      {
        label: 'Supplier Invoices',
        path: '/purchasing/invoices',
        icon: '🧾',
        description: 'Register and track supplier invoices against POs.',
        requiredPermission: 'purchasing.view',
      },
    ],
  },
  {
    label: 'Vendors',
    items: [
      {
        label: 'Supplier Dashboard',
        path: '/vendors',
        icon: '◆',
        description: 'Supplier performance, contracts, ratings, and spend.',
        requiredPermission: 'vendors.view',
      },
      {
        label: 'Suppliers',
        path: '/vendors/suppliers',
        icon: '▣',
        description: 'Companies, categories, contacts, and purchasing relationships.',
        requiredPermission: 'vendors.view',
      },
      {
        label: 'Contacts',
        path: '/vendors/contacts',
        icon: '◎',
        description: 'Primary, department, and emergency supplier contacts.',
        requiredPermission: 'vendors.view',
      },
      {
        label: 'Contracts',
        path: '/vendors/contracts',
        icon: '⌁',
        description: 'Contract values, renewals, and expiry timelines.',
        requiredPermission: 'vendors.view',
      },
      {
        label: 'Quotations',
        path: '/vendors/quotations',
        icon: '≋',
        description: 'RFQ references, quotation history, and price comparison.',
        requiredPermission: 'vendors.view',
      },
    ],
  },
  {
    label: 'Field Service',
    items: [
      {
        label: 'Work Order Dashboard',
        path: '/field-service',
        icon: '⚒',
        description: 'Installation pipeline, dispatch workload, and field-service KPIs.',
        requiredPermission: 'field.view',
      },
      {
        label: 'Installation Requests',
        path: '/field-service/installation-requests',
        icon: '⌂',
        description: 'Customer installation handoffs, assignment, and scheduling.',
        requiredPermission: 'field.view',
      },
      {
        label: 'Work Orders',
        path: '/field-service/work-orders',
        icon: '▤',
        description: 'Installations, repairs, maintenance, relocation, and surveys.',
        requiredPermission: 'field.view',
      },
      {
        label: 'Technician Schedule',
        path: '/field-service/schedule',
        icon: '◷',
        description: 'Daily technician calendar and unscheduled work queue.',
        requiredPermission: 'field.view',
      },
      {
        label: 'Field Activity',
        path: '/field-service/activity',
        icon: '◎',
        description: 'Mobile-first visits, logs, materials, and job completion.',
        requiredPermission: 'field.view',
      },
    ],
  },
  {
    label: 'Support',
    items: [
      {
        label: 'Helpdesk',
        path: '/support',
        icon: '🎧',
        description: 'Manage support tickets, assignments, timelines, and SLA performance.',
        requiredPermission: 'support.view',
      },
    ],
  },
  {
    label: 'Network',
    items: [
      {
        label: 'MikroTik Routers',
        path: '/network/routers',
        icon: '◉',
        description: 'Secure RouterOS connections, discovery, and health monitoring.',
        requiredPermission: 'mikrotik.view',
      },
      {
        label: 'PPPoE Management',
        path: '/network/pppoe',
        icon: '⇋',
        description: 'Enterprise PPPoE subscriber secrets, live active sessions, and synchronization.',
        requiredPermission: 'pppoe.view',
      },
    ],
  },
  {
    label: 'Reports & BI',
    items: [
      {
        label: 'Analytics Dashboard',
        path: '/reports',
        icon: '◫',
        description: 'Executive, finance, operations, network, support, and sales intelligence.',
        requiredPermission: 'analytics.view',
      },
      {
        label: 'Report Builder',
        path: '/reports/builder',
        icon: '▥',
        description: 'Generate reusable reports with shared dimensions and filters.',
        requiredPermission: 'reports.view',
      },
      {
        label: 'Saved Reports',
        path: '/reports/saved',
        icon: '☆',
        description: 'Run personal and shared report configurations.',
        requiredPermission: 'reports.view',
      },
      {
        label: 'Scheduled Reports',
        path: '/reports/scheduled',
        icon: '◷',
        description: 'Schedule-ready report definitions for future automation.',
        requiredPermission: 'reports.manage',
      },
      {
        label: 'Export Center',
        path: '/reports/exports',
        icon: '⇩',
        description: 'Download and review PDF, Excel, and CSV exports.',
        requiredPermission: 'reports.export',
      },
    ],
  },
  {
    label: 'Administration',
    items: [
      {
        label: 'System Administration',
        path: '/admin/system',
        icon: '⚙',
        description: 'Manage company profile, branches, branding, localization, and settings.',
        requiredPermission: 'system.view',
      },
      {
        label: 'Role Management',
        path: '/admin/roles',
        icon: '◇',
        description: 'Manage roles, permissions, and user role assignments.',
        allowedRoles: ADMIN_ROLES,
      },
    ],
  },
];

export const canViewNavigationItem = (item: NavigationItem, user: AuthUser | null, permissions: string[] = []): boolean => {
  if (!user) {
    return false;
  }
  if (item.requiredPermission && !permissions.includes('*') && !permissions.includes(item.requiredPermission)) {
    return false;
  }
  if (!item.allowedRoles?.length) {
    return true;
  }

  return user.roles.includes('Super Administrator') || item.allowedRoles.some((role) => user.roles.includes(role));
};
