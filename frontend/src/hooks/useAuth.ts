import { useCallback } from 'react';

import { logout } from '@/features/authentication/api/authApi';
import { setAccessToken } from '@/lib/apiClient';
import { useAppDispatch, useAppSelector } from '@/store/hooks';
import { sessionEnded } from '@/store/authSlice';

export const useAuth = () => {
  const dispatch = useAppDispatch();
  const auth = useAppSelector((state) => state.auth);

  const signOut = useCallback(async () => {
    try {
      await logout();
    } finally {
      setAccessToken(null);
      dispatch(sessionEnded());
    }
  }, [dispatch]);

  return { ...auth, signOut };
};
