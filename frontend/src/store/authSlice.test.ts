import { describe, test, expect } from 'vitest';
import { authReducer, authInitializationCompleted, sessionEnded, sessionStarted, type AuthState } from './authSlice';

describe('authSlice reducer', () => {
  const initialState: AuthState = {
    user: null,
    accessToken: null,
    isAuthenticated: false,
    isInitialized: false,
  };

  test('should return the initial state', () => {
    expect(authReducer(undefined, { type: 'unknown' })).toEqual(initialState);
  });

  test('should handle authInitializationCompleted', () => {
    const nextState = authReducer(initialState, authInitializationCompleted());
    expect(nextState.isInitialized).toBe(true);
    expect(nextState.isAuthenticated).toBe(false);
  });

  test('should handle sessionStarted', () => {
    const user = { id: '1', name: 'Alice', email: 'alice@skyfi.com', roles: ['Administrator'] };
    const session = { user, accessToken: 'dummy-token', refreshToken: 'dummy-refresh' };
    const nextState = authReducer(initialState, sessionStarted(session));

    expect(nextState.user).toEqual(user);
    expect(nextState.accessToken).toBe('dummy-token');
    expect(nextState.isAuthenticated).toBe(true);
    expect(nextState.isInitialized).toBe(true);
  });

  test('should handle sessionEnded', () => {
    const loggedInState: AuthState = {
      user: { id: '1', name: 'Alice', email: 'alice@skyfi.com', roles: ['Administrator'] },
      accessToken: 'dummy-token',
      isAuthenticated: true,
      isInitialized: true,
    };
    const nextState = authReducer(loggedInState, sessionEnded());

    expect(nextState.user).toBeNull();
    expect(nextState.accessToken).toBeNull();
    expect(nextState.isAuthenticated).toBe(false);
    expect(nextState.isInitialized).toBe(true);
  });
});
