import { describe, test, expect, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { usePermissions } from './usePermissions';
import { useAuth } from './useAuth';
import { apiClient } from '@/lib/apiClient';
import React from 'react';

vi.mock('./useAuth', () => ({
  useAuth: vi.fn(),
}));

vi.mock('@/lib/apiClient', () => ({
  apiClient: {
    get: vi.fn(),
  },
}));

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

describe('usePermissions hook', () => {
  test('should return permissions and can function', async () => {
    vi.mocked(useAuth).mockReturnValue({
      user: { id: '1', name: 'Alice', email: 'alice@skyfi.com', roles: [] },
      accessToken: 'token',
      isAuthenticated: true,
      isInitialized: true,
      signOut: vi.fn(),
    });

    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: ['billing.view', 'finance.manage'] },
    });

    const { result } = renderHook(() => usePermissions(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toEqual(['billing.view', 'finance.manage']);
    expect(result.current.can('billing.view')).toBe(true);
    expect(result.current.can('billing.edit')).toBe(false);
    expect(result.current.can('something')).toBe(false);
  });

  test('should allow all permissions if super user wildcard exists', async () => {
    vi.mocked(useAuth).mockReturnValue({
      user: { id: '1', name: 'Alice', email: 'alice@skyfi.com', roles: [] },
      accessToken: 'token',
      isAuthenticated: true,
      isInitialized: true,
      signOut: vi.fn(),
    });

    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: ['*'] },
    });

    const { result } = renderHook(() => usePermissions(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.can('any.random.permission')).toBe(true);
  });
});
