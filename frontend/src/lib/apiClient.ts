import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';

import { config } from '@/config';
import type { ApiErrorResponse, AuthSession } from '@/features/authentication/types';

interface ApiResourceResponse {
  data?: {
    attributes?: AuthSession;
  };
}

interface RetryableRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

let accessToken: string | null = null;
let refreshPromise: Promise<string> | null = null;
let onAccessTokenRefreshed: ((token: string) => void) | undefined;
let onAuthenticationRequired: (() => void) | undefined;

export const apiClient = axios.create({
  baseURL: config.apiBaseUrl,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  withCredentials: true,
});

export const setAccessToken = (token: string | null) => {
  accessToken = token;
};

export const configureAuthCallbacks = (callbacks: {
  onAccessTokenRefreshed: (token: string) => void;
  onAuthenticationRequired: () => void;
}) => {
  onAccessTokenRefreshed = callbacks.onAccessTokenRefreshed;
  onAuthenticationRequired = callbacks.onAuthenticationRequired;
};

const extractSession = (response: ApiResourceResponse): AuthSession => {
  const session = response.data?.attributes;
  if (!session?.accessToken || !session.user) {
    throw new Error('The authentication response was malformed.');
  }

  return session;
};

const refreshAccessToken = async (): Promise<string> => {
  if (!refreshPromise) {
    refreshPromise = axios
      .post<ApiResourceResponse>(`${config.apiBaseUrl}/auth/refresh`, undefined, { withCredentials: true })
      .then((response) => extractSession(response.data).accessToken)
      .then((token) => {
        setAccessToken(token);
        onAccessTokenRefreshed?.(token);
        return token;
      })
      .finally(() => {
        refreshPromise = null;
      });
  }

  return refreshPromise;
};

apiClient.interceptors.request.use((request) => {
  if (accessToken) {
    request.headers.set('Authorization', `Bearer ${accessToken}`);
  }

  return request;
});

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiErrorResponse>) => {
    const request = error.config as RetryableRequestConfig | undefined;
    const errorCode = error.response?.data?.errors?.[0]?.code;

    if (error.response?.status === 401 && errorCode === 'token_expired' && request && !request._retry) {
      request._retry = true;
      try {
        const token = await refreshAccessToken();
        request.headers.set('Authorization', `Bearer ${token}`);
        return apiClient(request);
      } catch (refreshError) {
        setAccessToken(null);
        onAuthenticationRequired?.();
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  },
);

export const apiErrorMessage = (error: unknown, fallback = 'Something went wrong. Please try again.') => {
  if (axios.isAxiosError<ApiErrorResponse>(error)) {
    return error.response?.data?.errors?.[0]?.detail ?? fallback;
  }

  return error instanceof Error ? error.message : fallback;
};
