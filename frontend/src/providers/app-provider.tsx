import { useEffect, type PropsWithChildren } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Provider } from 'react-redux';

import { refresh } from '@/features/authentication/api/authApi';
import { configureAuthCallbacks } from '@/lib/apiClient';
import { store } from '@/store';
import { authInitializationCompleted, sessionEnded, sessionStarted } from '@/store/authSlice';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 30_000, retry: 1 },
  },
});

const AuthInitializer = ({ children }: PropsWithChildren) => {
  useEffect(() => {
    configureAuthCallbacks({
      onAccessTokenRefreshed: (token) => {
        const currentState = store.getState().auth;
        if (currentState.user) {
          store.dispatch(sessionStarted({ accessToken: token, user: currentState.user }));
        }
      },
      onAuthenticationRequired: () => {
        store.dispatch(sessionEnded());
      },
    });

    refresh()
      .then((session) => store.dispatch(sessionStarted(session)))
      .catch(() => store.dispatch(authInitializationCompleted()));
  }, []);

  return children;
};

export const AppProvider = ({ children }: PropsWithChildren) => (
  <Provider store={store}>
    <QueryClientProvider client={queryClient}>
      <AuthInitializer>{children}</AuthInitializer>
    </QueryClientProvider>
  </Provider>
);
