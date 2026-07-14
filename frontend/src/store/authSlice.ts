import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

import type { AuthSession, AuthUser } from '@/features/authentication/types';
import { setAccessToken } from '@/lib/apiClient';

interface AuthState {
  user: AuthUser | null;
  accessToken: string | null;
  isAuthenticated: boolean;
  isInitialized: boolean;
}

const initialState: AuthState = {
  user: null,
  accessToken: null,
  isAuthenticated: false,
  isInitialized: false,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    sessionStarted: (state, action: PayloadAction<AuthSession>) => {
      state.user = action.payload.user;
      state.accessToken = action.payload.accessToken;
      state.isAuthenticated = true;
      state.isInitialized = true;
      setAccessToken(action.payload.accessToken);
    },
    sessionEnded: (state) => {
      state.user = null;
      state.accessToken = null;
      state.isAuthenticated = false;
      state.isInitialized = true;
      setAccessToken(null);
    },
    authInitializationCompleted: (state) => {
      state.isInitialized = true;
    },
  },
});

export const { authInitializationCompleted, sessionEnded, sessionStarted } = authSlice.actions;
export const authReducer = authSlice.reducer;
export type { AuthState };
