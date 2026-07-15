import { describe, test, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { ProtectedRoute } from './protected-route';
import { useAuth } from '@/hooks/useAuth';
import React from 'react';

vi.mock('@/hooks/useAuth', () => ({
  useAuth: vi.fn(),
}));

describe('ProtectedRoute component', () => {
  test('renders loading spinner when auth is not initialized', () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: false,
      isInitialized: false,
      user: null,
      accessToken: null,
      signOut: vi.fn(),
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<div>Dashboard</div>} />
          </Route>
        </Routes>
      </MemoryRouter>
    );

    expect(screen.getByLabelText(/loading/i)).toBeInTheDocument();
    expect(screen.queryByText('Dashboard')).not.toBeInTheDocument();
  });

  test('redirects to /login when not authenticated', () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: false,
      isInitialized: true,
      user: null,
      accessToken: null,
      signOut: vi.fn(),
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<div>Dashboard</div>} />
          </Route>
          <Route path="/login" element={<div>Login Page</div>} />
        </Routes>
      </MemoryRouter>
    );

    expect(screen.getByText('Login Page')).toBeInTheDocument();
    expect(screen.queryByText('Dashboard')).not.toBeInTheDocument();
  });

  test('renders child routes when authenticated', () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: true,
      isInitialized: true,
      user: { id: 1, name: 'Alice', email: 'alice@skyfi.com', roles: [] },
      accessToken: 'token',
      signOut: vi.fn(),
    });

    render(
      <MemoryRouter initialEntries={['/dashboard']}>
        <Routes>
          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<div>Dashboard Page Content</div>} />
          </Route>
        </Routes>
      </MemoryRouter>
    );

    expect(screen.getByText('Dashboard Page Content')).toBeInTheDocument();
  });
});
