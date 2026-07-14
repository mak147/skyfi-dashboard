import type { AuthUser } from '@/features/authentication/types';

const ADMIN_ROLES = ['Super Administrator'];

export interface NavigationItem {
  label: string;
  path: string;
  icon: string;
  description: string;
  allowedRoles?: string[];
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
    ],
  },
  {
    label: 'Administration',
    items: [
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

export const canViewNavigationItem = (item: NavigationItem, user: AuthUser | null): boolean => {
  if (!item.allowedRoles?.length) {
    return Boolean(user);
  }

  if (!user) {
    return false;
  }

  return user.roles.includes('Super Administrator') || item.allowedRoles.some((role) => user.roles.includes(role));
};
