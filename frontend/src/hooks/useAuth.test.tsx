import { describe, test, expect, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useAuth } from './useAuth';
import { useAppSelector, useAppDispatch } from '@/store/hooks';

vi.mock('@/store/hooks', () => ({
  useAppDispatch: vi.fn(),
  useAppSelector: vi.fn(),
}));

vi.mock('@/features/authentication/api/authApi', () => ({
  logout: vi.fn().mockResolvedValue(true),
}));

describe('useAuth hook', () => {
  test('should return state and signOut method', async () => {
    const dispatchMock = vi.fn();
    const stateMock = {
      user: { id: 1, name: 'Alice', email: 'alice@skyfi.com', roles: [] },
      accessToken: 'token',
      isAuthenticated: true,
      isInitialized: true,
    };

    vi.mocked(useAppDispatch).mockReturnValue(dispatchMock);
    vi.mocked(useAppSelector).mockImplementation((selector) => selector({ auth: stateMock }));

    const { result } = renderHook(() => useAuth());

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.user).toEqual({ id: 1, name: 'Alice', email: 'alice@skyfi.com', roles: [] });
    expect(typeof result.current.signOut).toBe('function');

    await result.current.signOut();
    expect(dispatchMock).toHaveBeenCalled();
  });
});
