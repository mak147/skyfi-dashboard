export interface AuthUser {
  id: string;
  name: string;
  email: string;
  roles: string[];
}

export interface AuthSession {
  accessToken: string;
  user: AuthUser;
}

export interface LoginPayload {
  email: string;
  password: string;
  rememberMe: boolean;
}

export interface ApiErrorItem {
  status?: string;
  code?: string;
  title?: string;
  detail?: string;
  source?: { pointer?: string };
}

export interface ApiErrorResponse {
  errors?: ApiErrorItem[];
}
